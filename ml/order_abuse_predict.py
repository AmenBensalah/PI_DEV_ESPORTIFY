import json
import os
import sys

import joblib
import numpy as np


def to_float(value, default=0.0):
    try:
        return float(value)
    except (TypeError, ValueError):
        return float(default)


def main():
    if len(sys.argv) < 4:
        print("Usage: python order_abuse_predict.py <model_pkl> <input_json> <output_json>")
        sys.exit(1)

    model_pkl = sys.argv[1]
    input_json = sys.argv[2]
    output_json = sys.argv[3]

    if not os.path.exists(model_pkl):
        print("Model file not found.")
        sys.exit(1)
    if not os.path.exists(input_json):
        print("Input json not found.")
        sys.exit(1)

    payload = joblib.load(model_pkl)
    model = payload["model"]
    features = payload.get("features", [])

    with open(input_json, "r", encoding="utf-8") as f:
        row = json.load(f)

    vector = np.array([[to_float(row.get(feature, 0.0), 0.0) for feature in features]], dtype=float)

    if model is None:
        prob = to_float(payload.get("default_prob", 0.0), 0.0)
    elif hasattr(model, "predict_proba"):
        prob = float(model.predict_proba(vector)[0][1])
    else:
        prob = float(model.predict(vector)[0])
        prob = max(0.0, min(1.0, prob))

    risk_score = round(prob * 100.0, 2)
    result = {
        "risk_score": risk_score,
        "probability": round(prob, 6),
        "source": "trained_ml_model",
    }

    with open(output_json, "w", encoding="utf-8") as f:
        json.dump(result, f, indent=2)

    print(json.dumps(result))


if __name__ == "__main__":
    main()
