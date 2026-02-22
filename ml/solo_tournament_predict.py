import json
import os
import sys
from datetime import datetime

import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import StratifiedKFold, cross_val_score
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler


FEATURES = [
    "prev_win_rate",
    "prev_game_win_rate",
    "prev_matches_scaled",
]


def clamp01(value):
    return max(0.0, min(1.0, float(value)))


def clamp100(value):
    return max(0.0, min(100.0, float(value)))


def to_float(value, default=0.0):
    try:
        return float(value)
    except (TypeError, ValueError):
        return float(default)


def to_int(value, default=0):
    try:
        return int(value)
    except (TypeError, ValueError):
        return int(default)


def read_csv(path):
    if not os.path.exists(path) or os.path.getsize(path) == 0:
        return pd.DataFrame()
    return pd.read_csv(path)


def normalize_game_type(value):
    key = str(value or "").strip().lower().replace("-", "_").replace(" ", "_")
    if key in ("battle_royale", "battleroyale"):
        return "battle_royale"
    if key == "sport":
        return "sports"
    return key if key else "other"


def build_training_frame(dataset_df):
    if dataset_df.empty or "label_win" not in dataset_df.columns:
        return pd.DataFrame(), pd.Series(dtype=int)

    train_df = pd.DataFrame()
    train_df["prev_win_rate"] = dataset_df.get("prev_win_rate", 0.0).astype(float)
    train_df["prev_game_win_rate"] = dataset_df.get("prev_game_win_rate", train_df["prev_win_rate"]).astype(float)
    train_df["prev_matches_scaled"] = dataset_df.get("prev_matches", 0.0).astype(float).clip(lower=0.0, upper=50.0) / 50.0
    y = dataset_df["label_win"].astype(int)
    return train_df, y


def choose_model(x_train, y_train):
    candidates = {
        "logistic_regression": Pipeline(
            [
                ("scaler", StandardScaler()),
                ("clf", LogisticRegression(max_iter=3000, random_state=42, class_weight="balanced")),
            ]
        ),
        "random_forest": RandomForestClassifier(
            n_estimators=400,
            max_depth=7,
            min_samples_leaf=2,
            random_state=42,
            class_weight="balanced_subsample",
            n_jobs=-1,
        ),
    }

    class_counts = y_train.value_counts()
    if len(class_counts) < 2 or int(class_counts.min()) < 2:
        model = candidates["logistic_regression"]
        model.fit(x_train, y_train)
        return model, {"selectedModel": "logistic_regression", "cvFolds": 0, "candidateScores": {}}

    folds = min(5, int(class_counts.min()))
    folds = max(2, folds)
    cv = StratifiedKFold(n_splits=folds, shuffle=True, random_state=42)

    scores = {}
    for name, model in candidates.items():
        try:
            val = cross_val_score(model, x_train, y_train, cv=cv, scoring="balanced_accuracy")
            scores[name] = float(np.mean(val))
        except Exception:
            scores[name] = None

    valid = {k: v for k, v in scores.items() if v is not None}
    selected_name = max(valid.keys(), key=lambda k: valid[k]) if valid else "logistic_regression"
    selected_model = candidates[selected_name]
    selected_model.fit(x_train, y_train)

    return selected_model, {
        "selectedModel": selected_name,
        "cvFolds": folds,
        "candidateScores": {
            k: (round(v, 4) if v is not None else None) for k, v in scores.items()
        },
    }


def softmax_probabilities(values):
    arr = np.array(values, dtype=float)
    if arr.size == 0:
        return []
    arr = arr - np.max(arr)
    exp_vals = np.exp(arr)
    denom = float(np.sum(exp_vals))
    if denom <= 0:
        return [0.0 for _ in values]
    return (exp_vals / denom).tolist()


def confidence_label(gap, matches):
    if gap >= 18.0 and matches >= 12:
        return "elevee"
    if gap >= 8.0 and matches >= 5:
        return "moyenne"
    return "faible"


def prior_probability(feats):
    # Prior metier: le win rate doit dominer la prediction.
    # prev_game_win_rate > prev_win_rate > experience
    return clamp01(
        (0.62 * feats["prev_game_win_rate"])
        + (0.33 * feats["prev_win_rate"])
        + (0.05 * feats["prev_matches_scaled"])
    )


def main():
    if len(sys.argv) < 4:
        print("Usage: python solo_tournament_predict.py <participants_csv> <dataset_csv> <output_json>")
        sys.exit(1)

    participants_csv = sys.argv[1]
    dataset_csv = sys.argv[2]
    output_json = sys.argv[3]

    participants_df = read_csv(participants_csv)
    dataset_df = read_csv(dataset_csv)

    os.makedirs(os.path.dirname(output_json), exist_ok=True)

    if participants_df.empty or len(participants_df) < 2:
        with open(output_json, "w", encoding="utf-8") as f:
            json.dump(
                {"available": False, "reason": "Au moins 2 participants sont requis."},
                f,
                ensure_ascii=False,
                indent=2,
            )
        return

    x_train, y_train = build_training_frame(dataset_df)
    model = None
    metrics = {"selectedModel": "fallback_blend", "cvFolds": 0, "candidateScores": {}}

    can_train = (not x_train.empty) and (len(y_train) >= 24) and (y_train.nunique() >= 2)
    if can_train:
        model, metrics = choose_model(x_train[FEATURES], y_train)

    rows = []
    for row in participants_df.to_dict("records"):
        overall_wr = clamp100(to_float(row.get("overall_win_rate"), 0.0))
        game_wr = clamp100(to_float(row.get("game_win_rate"), overall_wr))
        matches = max(0, to_int(row.get("matches_played"), 0))
        ml_prob = clamp100(to_float(row.get("ml_win_probability"), game_wr))

        feats = {
            "prev_win_rate": clamp01(overall_wr / 100.0),
            "prev_game_win_rate": clamp01(game_wr / 100.0),
            "prev_matches_scaled": clamp01(min(matches, 50) / 50.0),
        }

        prior_prob = prior_probability(feats)

        if model is None:
            model_prob = prior_prob
        else:
            pred_df = pd.DataFrame([feats], columns=FEATURES)
            model_prob = clamp01(float(model.predict_proba(pred_df)[0][1]))

        # Blend final:
        # - Prior metier (win rate) majoritaire
        # - ML de dataset en ajustement
        # - Prediction modele en correction legere
        blended_prob = clamp01(
            (0.60 * prior_prob)
            + (0.25 * (ml_prob / 100.0))
            + (0.15 * model_prob)
        )

        rows.append(
            {
                "userId": to_int(row.get("user_id"), 0),
                "name": str(row.get("name", "Unknown")),
                "overallWinRate": round(overall_wr, 1),
                "gameWinRate": round(game_wr, 1),
                "mlWinProbability": round(ml_prob, 1),
                "matchesPlayed": matches,
                "priorProbability": round(prior_prob * 100.0, 1),
                "modelProbability": round(model_prob * 100.0, 1),
                "blendedWinProbability": blended_prob,
                "gameType": normalize_game_type(row.get("game_type", "other")),
            }
        )

    normalized = softmax_probabilities([r["blendedWinProbability"] for r in rows])
    for i, item in enumerate(rows):
        item["winProbability"] = round(normalized[i] * 100.0, 1)

    rows.sort(key=lambda x: x["winProbability"], reverse=True)
    winner = rows[0]
    second = rows[1] if len(rows) > 1 else None
    gap = winner["winProbability"] - (second["winProbability"] if second else 0.0)
    confidence = confidence_label(gap, winner["matchesPlayed"])

    output = {
        "available": True,
        "generatedAt": datetime.utcnow().isoformat() + "Z",
        "gameType": winner["gameType"],
        "winner": {
            "userId": winner["userId"],
            "name": winner["name"],
            "winProbability": winner["winProbability"],
            "overallWinRate": winner["overallWinRate"],
            "gameWinRate": winner["gameWinRate"],
            "mlWinProbability": winner["mlWinProbability"],
            "matchesPlayed": winner["matchesPlayed"],
        },
        "rankings": [
            {
                "userId": r["userId"],
                "name": r["name"],
                "winProbability": r["winProbability"],
                "overallWinRate": r["overallWinRate"],
                "gameWinRate": r["gameWinRate"],
                "mlWinProbability": r["mlWinProbability"],
                "matchesPlayed": r["matchesPlayed"],
            }
            for r in rows
        ],
        "confidence": confidence,
        "modelInfo": metrics,
    }

    with open(output_json, "w", encoding="utf-8") as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    print(
        "solo_tournament_predict done. participants=%d model=%s"
        % (len(rows), metrics.get("selectedModel", "fallback_blend"))
    )


if __name__ == "__main__":
    main()
