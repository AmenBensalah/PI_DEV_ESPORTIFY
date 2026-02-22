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
    "availability_score",
    "motivation_len",
    "reason_len",
    "play_style_len",
]


def sigmoid(z: float) -> float:
    if z >= 0:
        e = math.exp(-z)
        return 1.0 / (1.0 + e)
    e = math.exp(z)
    return e / (1.0 + e)


def load_csv(path: str) -> Tuple[List[Dict[str, float]], List[int]]:
    rows: List[Dict[str, float]] = []
    labels: List[int] = []
    with open(path, "r", encoding="utf-8", newline="") as f:
        reader = csv.DictReader(f)
        for row in reader:
            item: Dict[str, float] = {}
            for k in FEATURES:
                item[k] = float(row.get(k, "0") or 0)
            label_raw = (row.get("label", "0") or "0").strip().lower()
            label = 1 if label_raw in ("1", "true", "accept", "accepted") else 0
            rows.append(item)
            labels.append(label)
    return rows, labels


def compute_norm(rows: List[Dict[str, float]]) -> Tuple[Dict[str, float], Dict[str, float]]:
    means: Dict[str, float] = {}
    stds: Dict[str, float] = {}
    n = max(1, len(rows))
    for k in FEATURES:
        mean = sum(r[k] for r in rows) / n
        var = sum((r[k] - mean) ** 2 for r in rows) / n
        std = math.sqrt(var)
        means[k] = mean
        stds[k] = std if std > 1e-9 else 1.0
    return means, stds


def norm_value(x: float, mean: float, std: float) -> float:
    return (x - mean) / std


def train_logreg(
    rows: List[Dict[str, float]],
    labels: List[int],
    means: Dict[str, float],
    stds: Dict[str, float],
    epochs: int = 1800,
    lr: float = 0.03,
    l2: float = 0.001,
) -> Tuple[Dict[str, float], float]:
    weights = {k: 0.0 for k in FEATURES}
    bias = 0.0

    n = max(1, len(rows))
    for _ in range(epochs):
        grad_w = {k: 0.0 for k in FEATURES}
        grad_b = 0.0
        for x, y in zip(rows, labels):
            z = bias
            normed = {}
            for k in FEATURES:
                nx = norm_value(x[k], means[k], stds[k])
                normed[k] = nx
                z += weights[k] * nx
            p = sigmoid(z)
            err = p - y
            grad_b += err
            for k in FEATURES:
                grad_w[k] += err * normed[k]

        for k in FEATURES:
            grad = (grad_w[k] / n) + (l2 * weights[k])
            weights[k] -= lr * grad
        bias -= lr * (grad_b / n)

    return weights, bias


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

    correct = 0
    for x, y in zip(rows, labels):
        z = bias
        for k in FEATURES:
            z += weights[k] * norm_value(x[k], means[k], stds[k])
        pred = 1 if sigmoid(z) >= 0.5 else 0
        if pred == y:
            correct += 1

    return {"samples": len(rows), "accuracy": round(correct / len(rows), 4)}


def main() -> int:
    if len(sys.argv) < 3:
        print("Usage: python ml/recruitment_match_train.py <dataset.csv> <model.json>")
        return 1

    dataset_path = sys.argv[1]
    model_path = sys.argv[2]
    if not os.path.isfile(dataset_path):
        print(f"Dataset not found: {dataset_path}")
        return 1

    rows, labels = load_csv(dataset_path)
    if len(rows) < 8:
        print("Not enough rows to train model (min 8).")
        return 1

    means, stds = compute_norm(rows)
    weights, bias = train_logreg(rows, labels, means, stds)
    metrics = evaluate(rows, labels, weights, bias, means, stds)

    payload = {
        "version": 1,
        "features": FEATURES,
        "weights": weights,
        "bias": bias,
        "means": means,
        "stds": stds,
        "metrics": metrics,
    }

    with open(model_path, "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=2)

    print(f"Model trained: {model_path}")
    print(json.dumps(metrics, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

