# User Performance AI (Isolated Module)

This module is fully separated from the product recommendation AI.

## 1) Files used by this module

- Core service (stats + insight): `src/Service/UserPerformanceAIService.php`
- ML prediction reader: `src/Service/UserPerformanceMLService.php`
- Profile API endpoint: `src/Controller/Api/UserPerformanceAiController.php`
- Training command: `src/Command/UserPerformanceTrainCommand.php`
- Python trainer: `ml/user_performance_train.py`
- Front widget: `public/js/user-performance-ai.js`, `public/css/user-performance-ai.css`

## 2) How the profile AI works

When a user opens `/profile`, the widget calls:

- `GET /api/profile/performance-ai`

The backend pipeline is:

1. Load tournaments where the user is registered (`participation` table).
2. Load played matches (`tournoi_match.status = played`) for these tournaments.
3. Detect if the user is in each match:
   - direct DB player link (`player_a_id` / `player_b_id`)
   - text alias match (`home_name` / `away_name`)
   - BR placement rows (`tournoi_match_participant_result`)
4. Compute stats (W/D/L, points, per-game win rate, trend, confidence).
5. Attach optional ML prediction from `var/user_ai/predictions.json`.
6. Return final JSON to the front widget.

## 3) Formula summary

- `matchesPlayed`: number of detected played matches
- `wins`, `draws`, `losses`: based on side score or BR placement
- `winRate = (wins / matchesPlayed) * 100`
- `averagePointsPerMatch = totalPoints / matchesPlayed`
- `trend`: based on last 5 results (`up`, `stable`, `down`)
- `confidence`: `low` / `medium` / `high` from sample count

## 4) Meaning of the Source counters

In profile widget data quality block:

- `Player links`
  - Match found by direct relation in DB (`player_a_id` or `player_b_id` = user id).
  - Most reliable source.

- `Name alias`
  - Match found by comparing `home_name` / `away_name` against user aliases.
  - Aliases include pseudo, full name, email, and team name.

- `Placements`
  - Match found from BR placement rows (`tournoi_match_participant_result`).
  - Used to compute result and placement points.

- `Ambigus`
  - Same user matched both sides (home and away) by text.
  - Counted as ambiguous to avoid false win/loss.

Example:

`Player links: 6 | Name alias: 3 | Placements: 4 | Ambigus: 0`

- 6 matches detected by direct player ids
- 3 matches detected only by alias text
- 4 placement-based detections
- 0 ambiguous detections

## 5) ML training flow

Run:

```bash
php bin/console app:user-ai:train
```

The command:

1. Builds `var/user_ai/train.csv` (historical samples with labels)
2. Builds `var/user_ai/predict.csv` (five rows per user: one per game type)
3. Runs `ml/user_performance_train.py`
4. Generates per-user predictions by game type:
   - `var/user_ai/predictions.json`
   - `var/user_ai/model_info.json`

`predictions.json` format:

- top key = `user_id`
- `byGameType` contains `fps`, `sports`, `battle_royale`, `mind`, `other`
- `bestGameType` and `bestWinProbability` summarize the strongest predicted context

Model logic:

- Main training mode:
  - tries multiple models (`logistic_regression_balanced`, `random_forest_balanced`, `hist_gradient_boosting`)
  - selects best model using cross-validation (`balanced_accuracy`)
  - applies probability smoothing for low game-specific sample sizes
- Fallback mode:
  - smart baseline by game type + user historical game win rate
  - used when data is too small or single-class

Features:

- `prev_matches`
- `prev_win_rate`
- `prev_draw_rate`
- `prev_loss_rate`
- `prev_avg_points`
- `prev_form5_score`
- `prev_form10_score`
- `prev_recent_win_streak`
- `prev_recent_loss_streak`
- `prev_game_matches`
- `prev_game_win_rate`
- `prev_game_draw_rate`
- `prev_game_loss_rate`
- `prev_game_avg_points`
- `prev_game_form5_score`
- `is_squad`
- `game_fps`, `game_sports`, `game_battle_royale`, `game_mind`, `game_other`

Label:

- `label_win = 1` if result is `W`, else `0`

## 6) Why you may see "Prediction ML indisponible"

This message appears when:

1. `app:user-ai:train` was not run yet, or failed
2. `var/user_ai/predictions.json` does not exist
3. Logged user has no generated prediction row (no valid match history)

Fix:

1. Install Python dependencies
2. Run `php bin/console app:user-ai:train`
3. Reload `/profile`

## 7) Python setup (Windows)

Install deps:

```bash
py -m pip install -r ml/requirements.txt
```

Then verify:

```bash
py --version
php bin/console app:user-ai:train
```

If training succeeds, you should see:

- `status=trained` or `status=fallback` in command output
- fresh JSON files in `var/user_ai/`

## 8) Auto retrain when results change

Auto retrain is now enabled through a Doctrine subscriber:

- `src/EventSubscriber/UserPerformanceAutoRetrainSubscriber.php`
- `src/Service/UserPerformanceAutoRetrainService.php`

Trigger condition:

- `tournoi_match` insert/delete
- `tournoi_match` update of `status`, `scoreA`, `scoreB`
- `tournoi_match_participant_result` insert/delete
- `tournoi_match_participant_result` update of `placement`, `points`, `participant`, `match`

When triggered, the app runs:

- `php bin/console app:user-ai:train`

The service uses a small cooldown (default 2 seconds) to prevent repeated retrain bursts in one editing session.
You can override with env:

- `USER_AI_AUTORETRAIN_COOLDOWN_SEC=0` for immediate retrain every flush

## 9) About the dataset file

The real dataset is built dynamically from DB history at training time.
By default it was generated under `var/user_ai/train.csv`, and `/var` is gitignored.

Now this command also writes visible snapshots in project root:

- `ml/user_performance_dataset.csv` (training rows)
- `ml/user_performance_predict_snapshot.csv` (prediction rows)
