import json
import os
import sys
from datetime import datetime

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import roc_auc_score
from sklearn.model_selection import StratifiedKFold, cross_val_score
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler


FEATURES = [
    "has_user_account",
    "total_orders",
    "pending_orders",
    "paid_orders",
    "cancelled_orders",
    "draft_orders",
    "identity_variants",
    "unpaid_ratio",
]


def load_csv(path):
    if not os.path.exists(path) or os.path.getsize(path) == 0:
        return pd.DataFrame()
    return pd.read_csv(path)


def choose_cv_folds(y):
    values = pd.Series(y).astype(int).value_counts()
    if len(values) < 2:
        return 0
    min_class = int(values.min())
    if min_class < 2:
        return 0
    folds = min(5, min_class)
    return folds if folds >= 2 else 0


def build_candidates():
    return {
        "logistic_regression": Pipeline(
            [
                ("scaler", StandardScaler()),
                (
                    "clf",
                    LogisticRegression(
                        max_iter=2000,
                        class_weight="balanced",
                        random_state=42,
                    ),
                ),
            ]
        ),
        "random_forest": RandomForestClassifier(
            n_estimators=400,
            max_depth=8,
            min_samples_split=4,
            min_samples_leaf=2,
            class_weight="balanced_subsample",
            random_state=42,
            n_jobs=-1,
        ),
    }


def pick_model(x, y):
    candidates = build_candidates()
    folds = choose_cv_folds(y)

    if folds < 2:
        selected = "logistic_regression"
        model = candidates[selected]
        model.fit(x, y)
        return model, {
            "selected_model": selected,
            "cv_folds": 0,
            "candidate_scores": {},
        }

    cv = StratifiedKFold(n_splits=folds, shuffle=True, random_state=42)
    scores = {}

    for name, model in candidates.items():
        try:
            cv_scores = cross_val_score(model, x, y, cv=cv, scoring="balanced_accuracy", error_score=np.nan)
            mean_score = float(np.nanmean(cv_scores)) if len(cv_scores) else float("nan")
            scores[name] = None if np.isnan(mean_score) else round(mean_score, 4)
        except Exception:
            scores[name] = None

    valid = {k: v for k, v in scores.items() if v is not None}
    selected = max(valid.keys(), key=lambda k: valid[k]) if valid else "logistic_regression"
    model = candidates[selected]
    model.fit(x, y)

    return model, {
        "selected_model": selected,
        "cv_folds": folds,
        "candidate_scores": scores,
    }


def main():
    if len(sys.argv) < 4:
        print("Usage: python order_abuse_train.py <dataset_csv> <model_pkl> <metadata_json>")
        sys.exit(1)

    dataset_csv = sys.argv[1]
    model_pkl = sys.argv[2]
    metadata_json = sys.argv[3]

    df = load_csv(dataset_csv)
    os.makedirs(os.path.dirname(model_pkl), exist_ok=True)
    os.makedirs(os.path.dirname(metadata_json), exist_ok=True)

    if len(df) == 0:
        with open(metadata_json, "w", encoding="utf-8") as f:
            json.dump(
                {
                    "generated_at": datetime.utcnow().isoformat() + "Z",
                    "status": "no_data",
                    "train_samples": 0,
                },
                f,
                indent=2,
            )
        print("No data to train.")
        return

    for col in FEATURES:
        if col not in df.columns:
            df[col] = 0.0
    if "label_abuse" not in df.columns:
        df["label_abuse"] = 0

    x = df[FEATURES].astype(float)
    y = df["label_abuse"].astype(int)
    unique_classes = sorted(pd.Series(y).unique().tolist())

    if len(unique_classes) < 2:
        default_prob = 1.0 if int(unique_classes[0]) == 1 else 0.0
        payload = {
            "model": None,
            "features": FEATURES,
            "threshold": 0.7,
            "single_class": int(unique_classes[0]),
            "default_prob": float(default_prob),
        }
        joblib.dump(payload, model_pkl)

        metadata = {
            "generated_at": datetime.utcnow().isoformat() + "Z",
            "status": "single_class_fallback",
            "train_samples": int(len(df)),
            "positive_rate": float(y.mean()) if len(y) else 0.0,
            "selected_model": "single_class_default",
            "cv_folds": 0,
            "candidate_scores": {},
            "train_auc": None,
            "features": FEATURES,
            "threshold": 0.7,
            "default_prob": float(default_prob),
        }
    else:
        model, metrics = pick_model(x, y)

        train_prob = None
        if hasattr(model, "predict_proba"):
            prob = model.predict_proba(x)[:, 1]
            train_prob = float(roc_auc_score(y, prob))

        payload = {
            "model": model,
            "features": FEATURES,
            "threshold": 0.7,
        }
        joblib.dump(payload, model_pkl)

        metadata = {
            "generated_at": datetime.utcnow().isoformat() + "Z",
            "status": "trained",
            "train_samples": int(len(df)),
            "positive_rate": float(y.mean()) if len(y) else 0.0,
            "selected_model": metrics["selected_model"],
            "cv_folds": metrics["cv_folds"],
            "candidate_scores": metrics["candidate_scores"],
            "train_auc": round(train_prob, 4) if train_prob is not None else None,
            "features": FEATURES,
            "threshold": 0.7,
        }
    with open(metadata_json, "w", encoding="utf-8") as f:
        json.dump(metadata, f, indent=2)

    print(
        "Model trained. samples=%d positive_rate=%.4f model=%s"
        % (len(df), metadata["positive_rate"], metadata["selected_model"])
    )


if __name__ == "__main__":
    main()
