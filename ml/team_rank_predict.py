#!/usr/bin/env python3
import json
import sys
from typing import Any, Dict, List


def clamp(value: float, low: float, high: float) -> float:
    return max(low, min(high, value))


def rank_from_score(score: float) -> str:
    if score >= 90:
        return "Challenger"
    if score >= 82:
        return "Master"
    if score >= 74:
        return "Diamant"
    if score >= 66:
        return "Platine"
    if score >= 58:
        return "Or"
    if score >= 50:
        return "Argent"
    return "Bronze"


def predict(payload: Dict[str, Any]) -> Dict[str, Any]:
    balance_score = float(payload.get("balanceScore", 0.0))
    accepted_last30 = float(payload.get("acceptedLast30", 0.0))
    total_last30 = float(payload.get("totalLast30", 0.0))
    trend_total = float(payload.get("trendTotal", 0.0))
    avg_level_score = float(payload.get("averageLevelScore", 0.0))
    is_active = bool(payload.get("isActive", False))
    members_count = float(payload.get("membersCount", 0.0))

    conversion = 0.0
    if total_last30 > 0:
        conversion = accepted_last30 / total_last30

    level_norm = clamp(avg_level_score / 4.0, 0.0, 1.0)
    balance_norm = clamp(balance_score / 100.0, 0.0, 1.0)
    trend_norm = clamp((trend_total + 100.0) / 200.0, 0.0, 1.0)
    member_norm = clamp(members_count / 8.0, 0.0, 1.0)
    activity = 1.0 if is_active else 0.3

    # Weighted score from team analytics dimensions
    overall = (
        0.30 * balance_norm
        + 0.25 * level_norm
        + 0.20 * conversion
        + 0.10 * trend_norm
        + 0.10 * activity
        + 0.05 * member_norm
    )
    rank_score = round(clamp(overall * 100.0, 0.0, 100.0), 1)
    confidence = round(clamp(45.0 + (conversion * 25.0) + (member_norm * 15.0) + (10.0 if is_active else 0.0), 0.0, 99.0), 1)
    rank_label = rank_from_score(rank_score)

    reasons: List[str] = []
    reasons.append(f"Équilibre d'équipe: {balance_score:.0f}/100")
    reasons.append(f"Niveau moyen: {avg_level_score:.2f}/4")
    reasons.append(f"Taux d'acceptation récent: {conversion * 100:.0f}%")
    reasons.append("Équipe active" if is_active else "Activité récente faible")

    return {
        "rank_label": rank_label,
        "rank_score": rank_score,
        "confidence": confidence,
        "reasons": reasons,
    }


def main() -> int:
    if len(sys.argv) != 3:
        print("Usage: python ml/team_rank_predict.py <input.json> <output.json>")
        return 1

    input_path = sys.argv[1]
    output_path = sys.argv[2]
    try:
        with open(input_path, "r", encoding="utf-8") as f:
            payload = json.load(f)
        result = predict(payload)
        with open(output_path, "w", encoding="utf-8") as f:
            json.dump(result, f, ensure_ascii=False, indent=2)
        return 0
    except Exception as exc:
        print(str(exc))
        return 1


if __name__ == "__main__":
    raise SystemExit(main())

