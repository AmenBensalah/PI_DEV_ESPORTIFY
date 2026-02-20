import json
import os
import sys
from datetime import datetime

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score
from sklearn.model_selection import TimeSeriesSplit


FEATURES = [
    "rev_1d",
    "rev_3d",
    "rev_7d",
    "rev_14d",
    "rev_30d",
    "orders_1d",
    "orders_3d",
    "orders_7d",
    "orders_14d",
    "orders_30d",
    "fail_1d",
    "fail_7d",
    "fail_30d",
    "dow",
    "is_weekend",
]

TARGETS = ["next_rev", "next_orders", "next_fail_rate"]


def load_csv(path):
    if not os.path.exists(path) or os.path.getsize(path) == 0:
        return pd.DataFrame()
    return pd.read_csv(path)


def evaluate_timeseries(model, x, y):
    if len(x) < 25:
        return {}

    splits = min(5, max(2, len(x) // 8))
    tscv = TimeSeriesSplit(n_splits=splits)

    maes = []
    r2s = []
    for train_idx, test_idx in tscv.split(x):
        x_train = x.iloc[train_idx]
        y_train = y.iloc[train_idx]
        x_test = x.iloc[test_idx]
        y_test = y.iloc[test_idx]

        model.fit(x_train, y_train)
        pred = model.predict(x_test)
        maes.append(float(mean_absolute_error(y_test, pred)))
        try:
            r2s.append(float(r2_score(y_test, pred, multioutput="uniform_average")))
        except Exception:
            pass

    return {
        "cv_splits": int(splits),
        "cv_mae_mean": round(float(np.mean(maes)), 4) if maes else None,
        "cv_r2_mean": round(float(np.mean(r2s)), 4) if r2s else None,
    }


def main():
    if len(sys.argv) < 4:
        print("Usage: python payment_forecast_train.py <dataset_csv> <model_pkl> <metadata_json>")
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
        print("No training data.")
        return

    for col in FEATURES + TARGETS:
        if col not in df.columns:
            df[col] = 0.0

    x = df[FEATURES].astype(float)
    y = df[TARGETS].astype(float)

    model = RandomForestRegressor(
        n_estimators=500,
        max_depth=10,
        min_samples_split=4,
        min_samples_leaf=2,
        random_state=42,
        n_jobs=-1,
    )

    cv_metrics = evaluate_timeseries(model, x, y)
    model.fit(x, y)

    pred_train = model.predict(x)
    train_mae = float(mean_absolute_error(y, pred_train))
    try:
        train_r2 = float(r2_score(y, pred_train, multioutput="uniform_average"))
    except Exception:
        train_r2 = None

    payload = {
        "model": model,
        "features": FEATURES,
        "targets": TARGETS,
    }
    joblib.dump(payload, model_pkl)

    metadata = {
        "generated_at": datetime.utcnow().isoformat() + "Z",
        "status": "trained",
        "model_type": "RandomForestRegressor",
        "train_samples": int(len(df)),
        "features": FEATURES,
        "targets": TARGETS,
        "train_mae": round(train_mae, 4),
        "train_r2": round(train_r2, 4) if train_r2 is not None else None,
        "cv_metrics": cv_metrics,
    }

    with open(metadata_json, "w", encoding="utf-8") as f:
        json.dump(metadata, f, indent=2)

    print(
        "Forecast model trained. samples=%d mae=%.4f r2=%s"
        % (len(df), train_mae, "n/a" if train_r2 is None else ("%.4f" % train_r2))
    )


if __name__ == "__main__":
    main()
