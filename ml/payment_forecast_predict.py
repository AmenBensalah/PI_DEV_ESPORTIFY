import json
import os
import sys

import joblib
import numpy as np


def to_float(v, default=0.0):
    try:
        return float(v)
    except (TypeError, ValueError):
        return float(default)


def main():
    if len(sys.argv) < 4:
        print("Usage: python payment_forecast_predict.py <model_pkl> <input_json> <output_json>")
        sys.exit(1)

    model_pkl = sys.argv[1]
    input_json = sys.argv[2]
    output_json = sys.argv[3]

    if not os.path.exists(model_pkl):
        print("Model file not found")
        sys.exit(1)
    if not os.path.exists(input_json):
        print("Input file not found")
        sys.exit(1)

    payload = joblib.load(model_pkl)
    model = payload.get("model")
    features = payload.get("features", [])

    with open(input_json, "r", encoding="utf-8") as f:
        row = json.load(f)

    x = np.array([[to_float(row.get(feature, 0.0), 0.0) for feature in features]], dtype=float)

    pred = model.predict(x)[0]
    forecast_revenue = max(0.0, to_float(pred[0], 0.0))
    forecast_orders = max(0.0, to_float(pred[1], 0.0))
    forecast_failure = min(100.0, max(0.0, to_float(pred[2], 0.0)))

    result = {
        "forecast_revenue_day": round(forecast_revenue, 2),
        "forecast_orders_day": int(round(forecast_orders)),
        "forecast_failure_rate_day": round(forecast_failure, 2),
        "source": "ml_forecast_model",
    }

    with open(output_json, "w", encoding="utf-8") as f:
        json.dump(result, f, indent=2)

    print(json.dumps(result))


if __name__ == "__main__":
    main()
