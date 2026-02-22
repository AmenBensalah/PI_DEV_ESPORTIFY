#!/usr/bin/env python3
import csv
import json
import math
import os
import sys
from typing import Dict, List, Tuple


FEATURES = [
    "rank_gap",
    "region_match",
    "style_match",
    "goals_match",
    "team_active",
    "team_size_score",
]


def sigmoid(z: float) -> float:
    if z >= 0:
        e = math.exp(-z)
        return 1.0 / (1.0 + e)
    e = math.exp(z)
    return e / (1.0 + e)


def load_rows(path: str, with_label: bool) -> Tuple[List[Dict[str, float]], List[int]]:
    rows: List[Dict[str, float]] = []
    labels: List[int] = []

    with open(path, "r", encoding="utf-8", newline="") as f:
        reader = csv.DictReader(f)
        for row in reader:
            item: Dict[str, float] = {}
            for feat in FEATURES:
                item[feat] = float(row.get(feat, "0") or 0.0)
            rows.append(item)

            if with_label:
                raw = (row.get("label", "0") or "0").strip().lower()
                labels.append(1 if raw in ("1", "true", "yes", "match") else 0)

    return rows, labels


def compute_norm(rows: List[Dict[str, float]]) -> Tuple[Dict[str, float], Dict[str, float]]:
    means: Dict[str, float] = {}
    stds: Dict[str, float] = {}
    n = max(1, len(rows))

    for feat in FEATURES:
        m = sum(r[feat] for r in rows) / n
        var = sum((r[feat] - m) ** 2 for r in rows) / n
        s = math.sqrt(var)
        means[feat] = m
        stds[feat] = s if s > 1e-9 else 1.0

    return means, stds


def z_norm(value: float, mean: float, std: float) -> float:
    return (value - mean) / std


def train_logreg(
    rows: List[Dict[str, float]],
    labels: List[int],
    means: Dict[str, float],
    stds: Dict[str, float],
    lr: float = 0.03,
    epochs: int = 1600,
    l2: float = 0.001,
) -> Tuple[Dict[str, float], float]:
    w = {feat: 0.0 for feat in FEATURES}
    b = 0.0
    n = max(1, len(rows))

    for _ in range(epochs):
        gw = {feat: 0.0 for feat in FEATURES}
        gb = 0.0

        for x, y in zip(rows, labels):
            z = b
            norm: Dict[str, float] = {}
            for feat in FEATURES:
                nx = z_norm(x[feat], means[feat], stds[feat])
                norm[feat] = nx
                z += w[feat] * nx

            p = sigmoid(z)
            err = p - y
            gb += err
            for feat in FEATURES:
                gw[feat] += err * norm[feat]

        for feat in FEATURES:
            grad = (gw[feat] / n) + (l2 * w[feat])
            w[feat] -= lr * grad
        b -= lr * (gb / n)

    return w, b


def predict_proba(
    row: Dict[str, float],
    weights: Dict[str, float],
    bias: float,
    means: Dict[str, float],
    stds: Dict[str, float],
) -> float:
    z = bias
    for feat in FEATURES:
        z += weights[feat] * z_norm(row[feat], means[feat], stds[feat])
    return sigmoid(z)


def evaluate(
    rows: List[Dict[str, float]],
    labels: List[int],
    weights: Dict[str, float],
    bias: float,
    means: Dict[str, float],
    stds: Dict[str, float],
) -> Dict[str, float]:
    if not rows:
        return {"samples": 0, "accuracy": 0.0}

    good = 0
    for x, y in zip(rows, labels):
        p = predict_proba(x, weights, bias, means, stds)
        pred = 1 if p >= 0.5 else 0
        if pred == y:
            good += 1

    return {
        "samples": len(rows),
        "accuracy": round(good / len(rows), 4),
    }


def cmd_train(dataset_csv: str, model_json: str) -> int:
    if not os.path.isfile(dataset_csv):
        print(f"Dataset not found: {dataset_csv}")
        return 1

    rows, labels = load_rows(dataset_csv, with_label=True)
    if len(rows) < 10:
        print("Not enough rows to train (min 10).")
        return 1

    means, stds = compute_norm(rows)
    weights, bias = train_logreg(rows, labels, means, stds)
    metrics = evaluate(rows, labels, weights, bias, means, stds)

    model = {
        "version": 1,
        "features": FEATURES,
        "weights": weights,
        "bias": bias,
        "means": means,
        "stds": stds,
        "metrics": metrics,
    }

    with open(model_json, "w", encoding="utf-8") as f:
        json.dump(model, f, ensure_ascii=False, indent=2)

    print(f"Model saved: {model_json}")
    print(json.dumps(metrics, ensure_ascii=False))
    return 0


def cmd_predict(model_json: str, candidates_csv: str, output_csv: str) -> int:
    if not os.path.isfile(model_json):
        print(f"Model not found: {model_json}")
        return 1
    if not os.path.isfile(candidates_csv):
        print(f"Candidates file not found: {candidates_csv}")
        return 1

    with open(model_json, "r", encoding="utf-8") as f:
        model = json.load(f)

    weights = model.get("weights", {})
    means = model.get("means", {})
    stds = model.get("stds", {})
    bias = float(model.get("bias", 0.0))

    rows, _ = load_rows(candidates_csv, with_label=False)

    with open(candidates_csv, "r", encoding="utf-8", newline="") as f:
        reader = csv.DictReader(f)
        raw_rows = list(reader)
        headers = list(reader.fieldnames or [])

    for i, row in enumerate(rows):
        w = {feat: float(weights.get(feat, 0.0)) for feat in FEATURES}
        m = {feat: float(means.get(feat, 0.0)) for feat in FEATURES}
        s = {feat: float(stds.get(feat, 1.0)) for feat in FEATURES}
        prob = predict_proba(row, w, bias, m, s)
        score = round(prob * 100.0, 2)
        raw_rows[i]["match_score"] = str(score)

    out_headers = headers + (["match_score"] if "match_score" not in headers else [])
    with open(output_csv, "w", encoding="utf-8", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=out_headers)
        writer.writeheader()
        writer.writerows(raw_rows)

    print(f"Predictions saved: {output_csv}")
    return 0


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage:")
        print("  python ml/team_matchmaking_train.py train <dataset.csv> <model.json>")
        print("  python ml/team_matchmaking_train.py predict <model.json> <candidates.csv> <output.csv>")
        return 1

    mode = sys.argv[1].strip().lower()
    if mode == "train":
        if len(sys.argv) != 4:
            print("Usage: python ml/team_matchmaking_train.py train <dataset.csv> <model.json>")
            return 1
        return cmd_train(sys.argv[2], sys.argv[3])

    if mode == "predict":
        if len(sys.argv) != 5:
            print("Usage: python ml/team_matchmaking_train.py predict <model.json> <candidates.csv> <output.csv>")
            return 1
        return cmd_predict(sys.argv[2], sys.argv[3], sys.argv[4])

    print(f"Unknown mode: {mode}")
    return 1


if __name__ == "__main__":
    raise SystemExit(main())

