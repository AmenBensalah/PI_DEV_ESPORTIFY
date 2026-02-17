import json
import os
import sys
from datetime import datetime

import numpy as np
import pandas as pd
from sklearn.ensemble import HistGradientBoostingClassifier, RandomForestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import StratifiedKFold, cross_val_score
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler


FEATURE_COLUMNS = [
    "prev_matches",
    "prev_win_rate",
    "prev_draw_rate",
    "prev_loss_rate",
    "prev_avg_points",
    "prev_form5_score",
    "prev_form10_score",
    "prev_recent_win_streak",
    "prev_recent_loss_streak",
    "prev_game_matches",
    "prev_game_win_rate",
    "prev_game_draw_rate",
    "prev_game_loss_rate",
    "prev_game_avg_points",
    "prev_game_form5_score",
    "is_squad",
    "game_fps",
    "game_sports",
    "game_battle_royale",
    "game_mind",
    "game_other",
]

GAME_TYPE_KEYS = ["fps", "sports", "battle_royale", "mind", "other"]
CV_METRIC = "balanced_accuracy"


def load_csv(path):
    if not os.path.exists(path) or os.path.getsize(path) == 0:
        return pd.DataFrame()
    return pd.read_csv(path)


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


def clamp01(value):
    return max(0.0, min(1.0, float(value)))


def user_confidence(sample_size):
    if sample_size >= 20:
        return "high"
    if sample_size >= 8:
        return "medium"
    return "low"


def resolve_game_key(row):
    game_key = str(row.get("game_key", "")).strip().lower()
    if game_key in GAME_TYPE_KEYS:
        return game_key

    if to_float(row.get("game_fps", 0)) >= 0.5:
        return "fps"
    if to_float(row.get("game_sports", 0)) >= 0.5:
        return "sports"
    if to_float(row.get("game_battle_royale", 0)) >= 0.5:
        return "battle_royale"
    if to_float(row.get("game_mind", 0)) >= 0.5:
        return "mind"
    return "other"


def build_game_baselines(train_df):
    if "label_win" in train_df.columns and len(train_df) > 0:
        global_baseline = clamp01(to_float(train_df["label_win"].mean(), 0.5))
    else:
        global_baseline = 0.5

    by_game = {}
    for game_key in GAME_TYPE_KEYS:
        col = {
            "fps": "game_fps",
            "sports": "game_sports",
            "battle_royale": "game_battle_royale",
            "mind": "game_mind",
            "other": "game_other",
        }[game_key]
        if col not in train_df.columns or "label_win" not in train_df.columns:
            by_game[game_key] = {"rate": global_baseline, "count": 0}
            continue

        subset = train_df[train_df[col].astype(float) >= 0.5]
        if len(subset) == 0:
            by_game[game_key] = {"rate": global_baseline, "count": 0}
        else:
            by_game[game_key] = {
                "rate": clamp01(to_float(subset["label_win"].mean(), global_baseline)),
                "count": int(len(subset)),
            }

    return {"global": global_baseline, "by_game": by_game}


def smooth_probability(model_prob, baseline_prob, game_samples):
    # If a user has little data in this game type, trust prior more than model output.
    weight = min(1.0, max(0.0, to_float(game_samples, 0.0)) / 8.0)
    return clamp01(weight * model_prob + (1.0 - weight) * baseline_prob)


def choose_cv_folds(y):
    values = pd.Series(y).astype(int).value_counts()
    if len(values) < 2:
        return 0

    min_class = int(values.min())
    if min_class < 2:
        return 0

    folds = min(5, min_class)
    if len(y) < 50:
        folds = min(folds, 3)

    return folds if folds >= 2 else 0


def build_model_candidates():
    return {
        "logistic_regression_balanced": Pipeline(
            [
                ("scaler", StandardScaler()),
                (
                    "clf",
                    LogisticRegression(
                        max_iter=4000,
                        random_state=42,
                        class_weight="balanced",
                        C=1.0,
                    ),
                ),
            ]
        ),
        "random_forest_balanced": RandomForestClassifier(
            n_estimators=500,
            max_depth=9,
            min_samples_split=4,
            min_samples_leaf=2,
            class_weight="balanced_subsample",
            random_state=42,
            n_jobs=-1,
        ),
        "hist_gradient_boosting": HistGradientBoostingClassifier(
            loss="log_loss",
            learning_rate=0.05,
            max_iter=350,
            max_depth=6,
            min_samples_leaf=8,
            l2_regularization=0.05,
            random_state=42,
        ),
    }


def pick_best_model(x_train, y_train):
    candidates = build_model_candidates()
    folds = choose_cv_folds(y_train)

    if folds < 2:
        name = "logistic_regression_balanced"
        model = candidates[name]
        model.fit(x_train, y_train)
        return model, {
            "selectedModel": name,
            "cvMetric": CV_METRIC,
            "cvFolds": 0,
            "candidateScores": {},
        }

    cv = StratifiedKFold(n_splits=folds, shuffle=True, random_state=42)
    scores = {}

    for name, model in candidates.items():
        try:
            cv_scores = cross_val_score(
                model,
                x_train,
                y_train,
                cv=cv,
                scoring=CV_METRIC,
                error_score=np.nan,
            )
            mean_score = float(np.nanmean(cv_scores)) if len(cv_scores) > 0 else float("nan")
            scores[name] = round(mean_score, 4) if not np.isnan(mean_score) else None
        except Exception:
            scores[name] = None

    valid_scores = {k: v for k, v in scores.items() if v is not None}
    if valid_scores:
        selected_name = max(valid_scores.keys(), key=lambda k: valid_scores[k])
    else:
        selected_name = "logistic_regression_balanced"

    selected_model = candidates[selected_name]
    selected_model.fit(x_train, y_train)

    return selected_model, {
        "selectedModel": selected_name,
        "cvMetric": CV_METRIC,
        "cvFolds": folds,
        "candidateScores": scores,
    }


def attach_best_game_summary(predictions):
    for user_id, payload in predictions.items():
        by_game = payload.get("byGameType", {})
        if not by_game:
            payload["bestGameType"] = None
            payload["bestWinProbability"] = 0.0
            continue

        best_key = max(
            by_game.keys(),
            key=lambda key: float(by_game[key].get("winProbability", 0.0)),
        )
        payload["bestGameType"] = best_key
        payload["bestWinProbability"] = float(
            by_game[best_key].get("winProbability", 0.0)
        )

    return predictions


def predict_with_fallback(train_df, predict_df):
    baselines = build_game_baselines(train_df)
    global_baseline = baselines["global"]

    predictions = {}
    for _, row in predict_df.iterrows():
        user_id = str(to_int(row.get("user_id"), 0))
        game_key = resolve_game_key(row)
        sample_size = to_int(row.get("sample_size", row.get("prev_matches", 0)), 0)
        game_samples = to_int(row.get("prev_game_matches", 0), 0)

        game_baseline = baselines["by_game"].get(game_key, {}).get("rate", global_baseline)
        personal_rate = clamp01(
            to_float(row.get("prev_game_win_rate", row.get("prev_win_rate", game_baseline)), game_baseline)
        )
        personal_weight = min(1.0, game_samples / 8.0)
        prob = clamp01(personal_weight * personal_rate + (1.0 - personal_weight) * game_baseline)

        if user_id not in predictions:
            predictions[user_id] = {"byGameType": {}}

        predictions[user_id]["byGameType"][game_key] = {
            "winProbability": round(prob * 100.0, 1),
            "expectedResult": "W" if prob >= 0.55 else "L",
            "confidence": user_confidence(sample_size),
            "samplesSeen": sample_size,
            "gameSamples": game_samples,
            "model": "fallback_smart_baseline",
            "gameType": game_key,
        }

    return attach_best_game_summary(predictions)


def train_and_predict(train_df, predict_df):
    x_train = train_df[FEATURE_COLUMNS].astype(float)
    y_train = train_df["label_win"].astype(int)

    model, selection_metrics = pick_best_model(x_train, y_train)

    x_predict = predict_df[FEATURE_COLUMNS].astype(float)
    probs_raw = model.predict_proba(x_predict)[:, 1]
    baselines = build_game_baselines(train_df)
    global_baseline = baselines["global"]

    predictions = {}
    for row, raw_prob in zip(predict_df.to_dict("records"), probs_raw):
        user_id = str(to_int(row.get("user_id"), 0))
        game_key = resolve_game_key(row)
        sample_size = to_int(row.get("sample_size", row.get("prev_matches", 0)), 0)
        game_samples = to_int(row.get("prev_game_matches", 0), 0)

        game_baseline = baselines["by_game"].get(game_key, {}).get("rate", global_baseline)
        prob = smooth_probability(clamp01(raw_prob), game_baseline, game_samples)

        if user_id not in predictions:
            predictions[user_id] = {"byGameType": {}}

        predictions[user_id]["byGameType"][game_key] = {
            "winProbability": round(prob * 100.0, 1),
            "rawWinProbability": round(clamp01(raw_prob) * 100.0, 1),
            "expectedResult": "W" if prob >= 0.55 else "L",
            "confidence": user_confidence(sample_size),
            "samplesSeen": sample_size,
            "gameSamples": game_samples,
            "model": selection_metrics["selectedModel"],
            "gameType": game_key,
        }

    predictions = attach_best_game_summary(predictions)

    metrics = {
        "trainAccuracy": round(float(model.score(x_train, y_train)), 4),
        "positiveRate": round(float(y_train.mean()), 4),
        "selectedModel": selection_metrics["selectedModel"],
        "cvMetric": selection_metrics["cvMetric"],
        "cvFolds": selection_metrics["cvFolds"],
        "candidateScores": selection_metrics["candidateScores"],
    }
    return predictions, metrics


def main():
    if len(sys.argv) < 5:
        print(
            "Usage: python user_performance_train.py <train_csv> <predict_csv> <predictions_json> <model_info_json>"
        )
        sys.exit(1)

    train_csv = sys.argv[1]
    predict_csv = sys.argv[2]
    predictions_json = sys.argv[3]
    model_info_json = sys.argv[4]

    train_df = load_csv(train_csv)
    predict_df = load_csv(predict_csv)

    os.makedirs(os.path.dirname(predictions_json), exist_ok=True)
    os.makedirs(os.path.dirname(model_info_json), exist_ok=True)

    model_info = {
        "generatedAt": datetime.utcnow().isoformat() + "Z",
        "features": FEATURE_COLUMNS,
        "trainSamples": int(len(train_df)),
        "predictSamples": int(len(predict_df)),
    }

    if len(predict_df) == 0:
        with open(predictions_json, "w", encoding="utf-8") as f:
            json.dump({}, f, indent=2)
        model_info["status"] = "no_predict_rows"
        with open(model_info_json, "w", encoding="utf-8") as f:
            json.dump(model_info, f, indent=2)
        print("No prediction rows. Empty prediction file written.")
        return

    missing_feature_cols = [col for col in FEATURE_COLUMNS if col not in train_df.columns]
    missing_predict_cols = [col for col in FEATURE_COLUMNS if col not in predict_df.columns]
    for col in missing_feature_cols:
        train_df[col] = 0.0
    for col in missing_predict_cols:
        predict_df[col] = 0.0
    if "label_win" not in train_df.columns:
        train_df["label_win"] = 0

    if len(train_df) < 24 or train_df["label_win"].nunique() < 2:
        predictions = predict_with_fallback(train_df, predict_df)
        model_info["status"] = "fallback"
        model_info["reason"] = "insufficient_rows_or_single_class"
    else:
        predictions, metrics = train_and_predict(train_df, predict_df)
        model_info["status"] = "trained"
        model_info.update(metrics)

    with open(predictions_json, "w", encoding="utf-8") as f:
        json.dump(predictions, f, indent=2)

    with open(model_info_json, "w", encoding="utf-8") as f:
        json.dump(model_info, f, indent=2)

    print(
        "Model run complete. train_samples=%d predict_samples=%d status=%s model=%s"
        % (
            model_info["trainSamples"],
            model_info["predictSamples"],
            model_info["status"],
            model_info.get("selectedModel", "fallback"),
        )
    )


if __name__ == "__main__":
    main()

