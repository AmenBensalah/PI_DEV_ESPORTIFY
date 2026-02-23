#!/usr/bin/env python3
import csv
import json
import os
import random
import re
import sys
import urllib.parse
import urllib.request
from collections import Counter, defaultdict
from datetime import datetime, timezone
from typing import Dict, Iterable, List, Optional, Set, Tuple

try:
    import joblib  # type: ignore
    import pandas as pd  # type: ignore
    from sklearn.feature_extraction.text import TfidfVectorizer  # type: ignore
    from sklearn.linear_model import LogisticRegression, Ridge  # type: ignore
    from sklearn.metrics import balanced_accuracy_score, f1_score, mean_absolute_error  # type: ignore
    from sklearn.model_selection import KFold, StratifiedKFold, cross_val_predict  # type: ignore
    from sklearn.pipeline import FeatureUnion, Pipeline  # type: ignore

    HAS_SKLEARN = True
except Exception:
    HAS_SKLEARN = False

try:
    from datasets import load_dataset  # type: ignore

    HAS_HF_DATASETS = True
except Exception:
    HAS_HF_DATASETS = False


RANDOM_SEED = 42
RNG = random.Random(RANDOM_SEED)
VALID_ACTIONS = {"allow", "review", "block"}
VALID_CATEGORIES = {"general", "tournoi", "recrutement", "resultat", "annonce", "drama", "event"}
TOKEN_RE = re.compile(r"[a-z0-9\u0600-\u06FF]{2,}", flags=re.IGNORECASE)


def normalize_text(value: str) -> str:
    return " ".join((value or "").strip().split())


def tokenize(text: str) -> List[str]:
    return [t.lower() for t in TOKEN_RE.findall(text or "")]


def clamp_score(value: int) -> int:
    return max(0, min(100, int(value)))


def safe_int(value, default: int = 0) -> int:
    try:
        return int(float(value))
    except Exception:
        return default


def parse_bool(value: str, default: bool = False) -> bool:
    raw = (value or "").strip().lower()
    if raw == "":
        return default
    return raw in {"1", "true", "yes", "on", "y"}


def parse_languages(value: str) -> Set[str]:
    langs = {x.strip().lower() for x in (value or "").split(",") if x.strip()}
    allowed = {"fr", "en", "ar"}
    clean = {x for x in langs if x in allowed}
    return clean if clean else {"fr", "en", "ar"}


def normalize_category(value: str) -> str:
    v = normalize_text(value).lower()
    if v in VALID_CATEGORIES:
        return v
    if "tournoi" in v or "tournament" in v:
        return "tournoi"
    if "recrut" in v or "tryout" in v or "lfp" in v or "lft" in v:
        return "recrutement"
    if "result" in v or "score" in v or "win" in v or "lost" in v:
        return "resultat"
    if "annonce" in v or "announce" in v or "news" in v or "update" in v:
        return "annonce"
    if "drama" in v or "clash" in v or "toxic" in v:
        return "drama"
    if "event" in v or "lan" in v or "bootcamp" in v:
        return "event"
    return "general"


def normalize_action(value: str) -> str:
    v = normalize_text(value).lower()
    return v if v in VALID_ACTIONS else "allow"


def infer_category_from_text(text: str) -> str:
    lower = (text or "").lower()
    if any(k in lower for k in ("tournoi", "tournament", "bracket", "final", "finale", "league", "cup", "scrim")):
        return "tournoi"
    if any(k in lower for k in ("recrutement", "tryout", "lft", "lfp", "join team", "searching player")):
        return "recrutement"
    if any(k in lower for k in ("resultat", "result", "score", "won", "lost", "victoire", "defaite", "mvp")):
        return "resultat"
    if any(k in lower for k in ("annonce", "announcement", "update", "news", "patch note")):
        return "annonce"
    if any(k in lower for k in ("drama", "clash", "beef", "toxic", "scandal", "controverse")):
        return "drama"
    if any(k in lower for k in ("event", "evenement", "lan", "bootcamp", "meetup")):
        return "event"
    return "general"


def infer_action_from_scores(toxicity: int, hate: int, spam: int) -> str:
    if hate >= 45 or toxicity >= 72 or spam >= 85:
        return "block"
    if hate >= 30 or toxicity >= 48 or spam >= 55:
        return "review"
    return "allow"


def make_row(
    text: str,
    category: str,
    auto_action: str,
    toxicity: int,
    hate: int,
    spam: int,
    source: str,
    entity_type: str = "web",
    entity_id: int = 0,
) -> Dict[str, object]:
    clean_text = normalize_text(text)
    return {
        "entity_type": entity_type,
        "entity_id": int(entity_id),
        "text": clean_text,
        "category": normalize_category(category),
        "auto_action": normalize_action(auto_action),
        "toxicity_score": clamp_score(toxicity),
        "hate_speech_score": clamp_score(hate),
        "spam_score": clamp_score(spam),
        "duplicate_score": 0,
        "media_risk_score": 0,
        "source": source,
    }


def load_local_rows(dataset_csv: str) -> List[Dict[str, object]]:
    rows: List[Dict[str, object]] = []
    with open(dataset_csv, "r", encoding="utf-8-sig", newline="") as f:
        reader = csv.DictReader(f)
        for raw in reader:
            text = normalize_text(str(raw.get("text", "")))
            if text == "":
                continue
            category = normalize_category(str(raw.get("category", "general")))
            action = normalize_action(str(raw.get("auto_action", "allow")))
            toxicity = clamp_score(safe_int(raw.get("toxicity_score", 0)))
            hate = clamp_score(safe_int(raw.get("hate_speech_score", 0)))
            spam = clamp_score(safe_int(raw.get("spam_score", 0)))
            rows.append(
                {
                    "entity_type": normalize_text(str(raw.get("entity_type", "local"))) or "local",
                    "entity_id": safe_int(raw.get("entity_id", 0), 0),
                    "text": text,
                    "category": category,
                    "auto_action": action,
                    "toxicity_score": toxicity,
                    "hate_speech_score": hate,
                    "spam_score": spam,
                    "duplicate_score": clamp_score(safe_int(raw.get("duplicate_score", 0))),
                    "media_risk_score": clamp_score(safe_int(raw.get("media_risk_score", 0))),
                    "source": "local_db",
                }
            )
    return rows


def augment_rows(rows: List[Dict[str, object]], factor: int) -> List[Dict[str, object]]:
    factor = max(1, min(8, int(factor)))
    if factor <= 1:
        return list(rows)

    augmented: List[Dict[str, object]] = list(rows)
    for row in rows:
        base_text = normalize_text(str(row.get("text", "")))
        if base_text == "":
            continue

        variants = [
            base_text.lower(),
            re.sub(r"[!?.,;:]+", " ", base_text).strip(),
            re.sub(r"\s+", " ", base_text.replace("#", " #")).strip(),
            f"{base_text} {row.get('category', 'general')}",
            " ".join(tokenize(base_text)[:40]),
        ]
        added = 1
        for variant in variants:
            if added >= factor:
                break
            v = normalize_text(variant)
            if v == "":
                continue
            clone = dict(row)
            clone["text"] = v
            clone["source"] = str(row.get("source", "local")) + "_aug"
            augmented.append(clone)
            added += 1
    return augmented


def dedupe_rows(rows: List[Dict[str, object]]) -> List[Dict[str, object]]:
    out: List[Dict[str, object]] = []
    seen = set()
    for row in rows:
        text = normalize_text(str(row.get("text", "")))
        if text == "":
            continue
        key = re.sub(r"\s+", " ", text.lower()).strip()
        if len(key) > 500:
            key = key[:500]
        if key in seen:
            continue
        seen.add(key)
        row["text"] = text
        out.append(row)
    return out


def balance_by_label(rows: List[Dict[str, object]], label_key: str, max_ratio: float, min_keep: int) -> List[Dict[str, object]]:
    groups: Dict[str, List[Dict[str, object]]] = defaultdict(list)
    for row in rows:
        label = str(row.get(label_key, ""))
        groups[label].append(row)

    if len(groups) <= 1:
        return rows

    min_count = min(len(v) for v in groups.values())
    cap = max(min_keep, int(max(1, min_count) * max_ratio))
    balanced: List[Dict[str, object]] = []
    for _, items in groups.items():
        if len(items) > cap:
            balanced.extend(RNG.sample(items, cap))
        else:
            balanced.extend(items)
    RNG.shuffle(balanced)
    return balanced


def write_dataset_csv(path: str, rows: List[Dict[str, object]]) -> None:
    os.makedirs(os.path.dirname(path), exist_ok=True)
    headers = [
        "entity_type",
        "entity_id",
        "text",
        "category",
        "auto_action",
        "toxicity_score",
        "hate_speech_score",
        "spam_score",
        "duplicate_score",
        "media_risk_score",
        "source",
    ]
    with open(path, "w", encoding="utf-8", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=headers)
        writer.writeheader()
        for row in rows:
            writer.writerow(
                {
                    "entity_type": row.get("entity_type", "web"),
                    "entity_id": row.get("entity_id", 0),
                    "text": row.get("text", ""),
                    "category": row.get("category", "general"),
                    "auto_action": row.get("auto_action", "allow"),
                    "toxicity_score": row.get("toxicity_score", 0),
                    "hate_speech_score": row.get("hate_speech_score", 0),
                    "spam_score": row.get("spam_score", 0),
                    "duplicate_score": row.get("duplicate_score", 0),
                    "media_risk_score": row.get("media_risk_score", 0),
                    "source": row.get("source", "unknown"),
                }
            )


def _extract_text_generic(item: Dict[str, object]) -> str:
    for key in ("text", "sms", "message", "content", "sentence", "tweet"):
        value = item.get(key)
        if isinstance(value, str) and value.strip():
            return normalize_text(value)
    return ""


def _safe_load_dataset(path: str, name: Optional[str], split: Optional[str]) -> Tuple[Optional[object], str]:
    if not HAS_HF_DATASETS:
        return None, "datasets_library_missing"
    try:
        if split is None:
            ds = load_dataset(path, name)
        elif name is None:
            ds = load_dataset(path, split=split)
        else:
            ds = load_dataset(path, name, split=split)
        return ds, ""
    except Exception as exc:
        return None, str(exc)


def _iter_dataset_rows(ds_obj: object) -> Iterable[Dict[str, object]]:
    if ds_obj is None:
        return []
    if hasattr(ds_obj, "keys") and hasattr(ds_obj, "__getitem__"):
        try:
            for split_name in ds_obj.keys():  # type: ignore[attr-defined]
                split_obj = ds_obj[split_name]  # type: ignore[index]
                for item in split_obj:
                    if isinstance(item, dict):
                        yield item
            return
        except Exception:
            pass
    try:
        for item in ds_obj:  # type: ignore[assignment]
            if isinstance(item, dict):
                yield item
    except Exception:
        return


def collect_tweet_eval(config: str, total_limit: int) -> Tuple[List[Dict[str, object]], Dict[str, object]]:
    source_name = f"tweet_eval_{config}"
    if total_limit <= 0:
        return [], {"source": source_name, "status": "skipped", "rows": 0}

    ds_obj, err = _safe_load_dataset("tweet_eval", config, "train+validation+test")
    if ds_obj is None:
        return [], {"source": source_name, "status": "error", "rows": 0, "error": err}

    pos_limit = max(1, total_limit // 2)
    neg_limit = max(1, total_limit - pos_limit)
    pos_count = 0
    neg_count = 0
    rows: List[Dict[str, object]] = []

    for item in _iter_dataset_rows(ds_obj):
        text = normalize_text(str(item.get("text", "")))
        if text == "":
            continue
        label = safe_int(item.get("label", 0), 0)
        if label not in (0, 1):
            continue

        if label == 1 and pos_count >= pos_limit:
            continue
        if label == 0 and neg_count >= neg_limit:
            continue

        if config == "hate":
            if label == 1:
                row = make_row(text, "drama", "block", 72, 88, 8, source_name)
            else:
                row = make_row(text, infer_category_from_text(text), "allow", 8, 2, 4, source_name)
        else:
            if label == 1:
                row = make_row(text, "drama", "review", 74, 24, 6, source_name)
            else:
                row = make_row(text, infer_category_from_text(text), "allow", 8, 2, 4, source_name)

        rows.append(row)
        if label == 1:
            pos_count += 1
        else:
            neg_count += 1

        if pos_count >= pos_limit and neg_count >= neg_limit:
            break

    return rows, {
        "source": source_name,
        "status": "ok",
        "rows": len(rows),
        "positive": pos_count,
        "negative": neg_count,
    }


def _is_spam_label(label) -> bool:
    if isinstance(label, bool):
        return bool(label)
    if isinstance(label, (int, float)):
        return int(label) == 1
    raw = str(label).strip().lower()
    return raw in {"1", "spam", "true", "yes", "positive"}


def collect_sms_spam(total_limit: int) -> Tuple[List[Dict[str, object]], Dict[str, object]]:
    source_name = "sms_spam"
    if total_limit <= 0:
        return [], {"source": source_name, "status": "skipped", "rows": 0}

    candidates = [
        ("sms_spam", None),
        ("SetFit/sms_spam", None),
    ]
    ds_obj = None
    last_error = ""
    for dataset_name, dataset_config in candidates:
        ds_obj, err = _safe_load_dataset(dataset_name, dataset_config, None)
        if ds_obj is not None:
            break
        last_error = err

    if ds_obj is None:
        return [], {"source": source_name, "status": "error", "rows": 0, "error": last_error}

    spam_limit = max(1, total_limit // 2)
    ham_limit = max(1, total_limit - spam_limit)
    spam_count = 0
    ham_count = 0
    rows: List[Dict[str, object]] = []
    for item in _iter_dataset_rows(ds_obj):
        text = _extract_text_generic(item)
        if text == "":
            continue
        label = item.get("label", item.get("target", "ham"))
        is_spam = _is_spam_label(label)
        if is_spam and spam_count >= spam_limit:
            continue
        if (not is_spam) and ham_count >= ham_limit:
            continue

        if is_spam:
            row = make_row(text, "annonce", "block", 20, 5, 95, source_name)
            spam_count += 1
        else:
            row = make_row(text, "general", "allow", 4, 1, 5, source_name)
            ham_count += 1
        rows.append(row)
        if spam_count >= spam_limit and ham_count >= ham_limit:
            break

    return rows, {
        "source": source_name,
        "status": "ok",
        "rows": len(rows),
        "spam": spam_count,
        "ham": ham_count,
    }


def collect_wikitext_clean(total_limit: int) -> Tuple[List[Dict[str, object]], Dict[str, object]]:
    source_name = "wikitext_clean"
    if total_limit <= 0:
        return [], {"source": source_name, "status": "skipped", "rows": 0}

    ds_obj, err = _safe_load_dataset("wikitext", "wikitext-2-raw-v1", "train")
    if ds_obj is None:
        return [], {"source": source_name, "status": "error", "rows": 0, "error": err}

    rows: List[Dict[str, object]] = []
    for item in _iter_dataset_rows(ds_obj):
        text = _extract_text_generic(item)
        if text == "":
            continue
        if len(text) < 60 or len(text) > 340:
            continue
        if text.startswith("=") or "http://" in text or "https://" in text:
            continue
        category = infer_category_from_text(text)
        rows.append(make_row(text, category, "allow", 5, 1, 4, source_name))
        if len(rows) >= total_limit:
            break

    return rows, {"source": source_name, "status": "ok", "rows": len(rows)}


def _fetch_wikipedia_summary(query: str, lang: str) -> str:
    q = normalize_text(query)
    if q == "":
        return ""

    encoded = urllib.parse.quote(q)
    try:
        search_url = (
            f"https://{lang}.wikipedia.org/w/api.php?action=opensearch"
            f"&search={encoded}&limit=1&namespace=0&format=json"
        )
        with urllib.request.urlopen(search_url, timeout=4.5) as resp:  # nosec B310
            payload = json.loads(resp.read().decode("utf-8", errors="ignore"))
        if not isinstance(payload, list) or len(payload) < 2 or not isinstance(payload[1], list) or not payload[1]:
            return ""

        title = normalize_text(str(payload[1][0]))
        if title == "":
            return ""

        title_encoded = urllib.parse.quote(title.replace(" ", "_"))
        summary_url = f"https://{lang}.wikipedia.org/api/rest_v1/page/summary/{title_encoded}"
        with urllib.request.urlopen(summary_url, timeout=4.5) as resp:  # nosec B310
            summary_obj = json.loads(resp.read().decode("utf-8", errors="ignore"))
        summary = normalize_text(str(summary_obj.get("extract", "")))
        return summary
    except Exception:
        return ""


def collect_wikipedia_esports(total_limit: int, languages: Set[str]) -> Tuple[List[Dict[str, object]], Dict[str, object]]:
    source_name = "wikipedia_esports"
    if total_limit <= 0:
        return [], {"source": source_name, "status": "skipped", "rows": 0}

    topic_map = {
        "tournoi": [
            "Esports tournament",
            "League of Legends World Championship",
            "Valorant Champions",
            "Counter-Strike Major Championships",
            "Dota 2 The International",
        ],
        "recrutement": [
            "Esports team",
            "Professional gaming",
            "Esports coach",
            "Esports player",
        ],
        "resultat": [
            "Esports",
            "Competitive video gaming",
            "Esports world rankings",
        ],
        "annonce": [
            "Esports organization",
            "Game update",
            "Patch notes",
            "Esports industry",
        ],
        "event": [
            "LAN party",
            "Gaming convention",
            "DreamHack",
            "Electronic Sports World Cup",
        ],
        "drama": [
            "Cheating in esports",
            "Esports controversy",
            "Match fixing",
        ],
    }

    query_rows: List[Tuple[str, str]] = []
    for category, topics in topic_map.items():
        for topic in topics:
            query_rows.append((category, topic))

    RNG.shuffle(query_rows)
    rows: List[Dict[str, object]] = []
    attempted = 0
    for category, query in query_rows:
        for lang in sorted(languages):
            attempted += 1
            summary = _fetch_wikipedia_summary(query, lang)
            if summary == "":
                continue
            toxicity = 4 if category != "drama" else 18
            hate = 1 if category != "drama" else 8
            spam = 3
            action = infer_action_from_scores(toxicity, hate, spam)
            rows.append(make_row(summary, category, action, toxicity, hate, spam, source_name))
            if len(rows) >= total_limit:
                break
        if len(rows) >= total_limit:
            break

    return rows, {
        "source": source_name,
        "status": "ok",
        "rows": len(rows),
        "attempted_queries": attempted,
    }


def collect_synthetic_esports(total_limit: int, languages: Set[str]) -> Tuple[List[Dict[str, object]], Dict[str, object]]:
    source_name = "synthetic_esports"
    if total_limit <= 0:
        return [], {"source": source_name, "status": "skipped", "rows": 0}

    games = ["Valorant", "CS2", "League of Legends", "Dota 2", "Rocket League", "Fortnite", "Apex Legends"]
    teams = ["Nexus", "Orbit", "Phoenix", "Titans", "Falcons", "Pulse", "Hydra", "Vortex"]
    regions = ["EU", "MENA", "NA", "APAC"]
    roles = ["duelist", "support", "sniper", "captain", "coach", "entry fragger"]
    levels = ["intermediaire", "avance", "semi-pro", "pro"]
    cities = ["Tunis", "Paris", "Berlin", "Dubai", "Casablanca", "Lyon"]
    days = ["samedi", "dimanche", "ce soir", "demain"]

    templates = {
        "fr": {
            "tournoi": "Tournoi {game} ce {day}, inscription ouverte pour les equipes {region}.",
            "recrutement": "Notre equipe {team} recrute un {role} niveau {level} pour {game}.",
            "resultat": "{team_a} a battu {team_b} 2-1 en match {game}.",
            "annonce": "Annonce officielle: mise a jour {game} et nouvelles regles de ligue.",
            "event": "Event LAN {game} a {city}: places limitees, ouverture des portes a 18h.",
            "drama": "Drama: clash entre {team_a} et {team_b} apres un scrim {game}.",
        },
        "en": {
            "tournoi": "{game} tournament this weekend, registration open for {region} teams.",
            "recrutement": "Team {team} is recruiting a {role} ({level}) for {game}.",
            "resultat": "{team_a} defeated {team_b} 2-1 in a {game} series.",
            "annonce": "Official announcement: {game} update and new league rules are live.",
            "event": "{game} LAN event in {city}: limited seats, doors open at 6 PM.",
            "drama": "Drama alert: conflict between {team_a} and {team_b} after scrims.",
        },
        "ar": {
            "tournoi": "بطولة {game} هذا الأسبوع والتسجيل مفتوح لفرق {region}.",
            "recrutement": "فريق {team} يبحث عن لاعب {role} بمستوى {level} للعبة {game}.",
            "resultat": "فاز {team_a} على {team_b} بنتيجة 2-1 في مباراة {game}.",
            "annonce": "إعلان رسمي: تحديث جديد للعبة {game} وقوانين دوري جديدة.",
            "event": "حدث LAN للعبة {game} في {city} والمقاعد محدودة.",
            "drama": "دراما: خلاف بين {team_a} و {team_b} بعد تدريب {game}.",
        },
    }

    lang_list = sorted(languages if languages else {"fr", "en", "ar"})
    categories = ["tournoi", "recrutement", "resultat", "annonce", "event", "drama"]

    rows: List[Dict[str, object]] = []
    per_category = max(1, total_limit // len(categories))
    for category in categories:
        added_for_category = 0
        while added_for_category < per_category and len(rows) < total_limit:
            lang = RNG.choice(lang_list)
            tmpl = templates.get(lang, templates["fr"]).get(category, templates["fr"]["annonce"])
            text = tmpl.format(
                game=RNG.choice(games),
                day=RNG.choice(days),
                region=RNG.choice(regions),
                team=RNG.choice(teams),
                team_a=RNG.choice(teams),
                team_b=RNG.choice(teams),
                role=RNG.choice(roles),
                level=RNG.choice(levels),
                city=RNG.choice(cities),
            )
            toxicity = 4 if category != "drama" else 24
            hate = 1 if category != "drama" else 9
            spam = 3
            action = infer_action_from_scores(toxicity, hate, spam)
            rows.append(make_row(text, category, action, toxicity, hate, spam, source_name))
            added_for_category += 1
        if len(rows) >= total_limit:
            break

    return rows, {"source": source_name, "status": "ok", "rows": len(rows)}


def collect_web_rows(max_rows: int, languages: Set[str]) -> Tuple[List[Dict[str, object]], List[Dict[str, object]]]:
    max_rows = max(0, int(max_rows))
    if max_rows == 0:
        return [], [{"source": "web", "status": "skipped", "rows": 0}]

    plan = {
        "tweet_eval_hate": int(max_rows * 0.22),
        "tweet_eval_offensive": int(max_rows * 0.22),
        "sms_spam": int(max_rows * 0.18),
        "wikitext_clean": int(max_rows * 0.16),
        "wikipedia_esports": int(max_rows * 0.08),
        "synthetic_esports": int(max_rows * 0.14),
    }

    planned = sum(plan.values())
    if planned < max_rows:
        plan["wikitext_clean"] += max_rows - planned

    rows: List[Dict[str, object]] = []
    stats: List[Dict[str, object]] = []

    hate_rows, hate_meta = collect_tweet_eval("hate", plan["tweet_eval_hate"])
    rows.extend(hate_rows)
    stats.append(hate_meta)

    off_rows, off_meta = collect_tweet_eval("offensive", plan["tweet_eval_offensive"])
    rows.extend(off_rows)
    stats.append(off_meta)

    spam_rows, spam_meta = collect_sms_spam(plan["sms_spam"])
    rows.extend(spam_rows)
    stats.append(spam_meta)

    wiki_text_rows, wiki_text_meta = collect_wikitext_clean(plan["wikitext_clean"])
    rows.extend(wiki_text_rows)
    stats.append(wiki_text_meta)

    wiki_es_rows, wiki_es_meta = collect_wikipedia_esports(plan["wikipedia_esports"], languages)
    rows.extend(wiki_es_rows)
    stats.append(wiki_es_meta)

    synthetic_rows, synthetic_meta = collect_synthetic_esports(plan["synthetic_esports"], languages)
    rows.extend(synthetic_rows)
    stats.append(synthetic_meta)

    rows = dedupe_rows(rows)
    if len(rows) > max_rows:
        rows = RNG.sample(rows, max_rows)

    return rows, stats


def train_nb_classifier(rows: List[Dict[str, object]], label_key: str) -> Dict[str, object]:
    labels = [str(r[label_key]) for r in rows]
    class_counts = Counter(labels)
    if len(class_counts) < 2 or len(rows) < 12:
        return {"trained": False, "reason": "insufficient_rows_or_single_class", "samples": len(rows)}

    token_counts: Dict[str, Counter] = defaultdict(Counter)
    total_tokens: Dict[str, int] = defaultdict(int)
    vocab = set()
    for r in rows:
        y = str(r[label_key])
        toks = tokenize(str(r["text"]))
        for t in toks:
            token_counts[y][t] += 1
            total_tokens[y] += 1
            vocab.add(t)

    payload = {
        "model_type": "naive_bayes_text",
        "label_key": label_key,
        "classes": sorted(class_counts.keys()),
        "class_counts": dict(class_counts),
        "token_counts": {k: dict(v) for k, v in token_counts.items()},
        "total_tokens": dict(total_tokens),
        "vocab_size": max(1, len(vocab)),
        "samples": len(rows),
    }
    return {
        "trained": True,
        "payload": payload,
        "samples": len(rows),
        "classes": sorted(class_counts.keys()),
    }


def train_token_mean_regressor(rows: List[Dict[str, object]], target_key: str) -> Dict[str, object]:
    if len(rows) < 12:
        return {"trained": False, "reason": "insufficient_rows", "samples": len(rows)}

    sums: Dict[str, float] = defaultdict(float)
    counts: Dict[str, int] = defaultdict(int)
    targets: List[int] = []
    for r in rows:
        y = clamp_score(safe_int(r[target_key], 0))
        targets.append(y)
        seen = set(tokenize(str(r["text"])))
        for t in seen:
            sums[t] += y
            counts[t] += 1

    if len(set(targets)) <= 1:
        return {"trained": False, "reason": "no_target_variance", "samples": len(rows)}

    token_means = {t: round(sums[t] / counts[t], 4) for t in counts if counts[t] >= 2}
    global_mean = round(sum(targets) / max(1, len(targets)), 4)
    payload = {
        "model_type": "token_mean_regressor",
        "target_key": target_key,
        "global_mean": global_mean,
        "token_means": token_means,
        "samples": len(rows),
    }
    return {"trained": True, "payload": payload, "samples": len(rows), "global_mean": global_mean}


def save_json_model(path: str, payload: Dict[str, object]) -> None:
    with open(path, "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False)


def train_with_fallback(rows: List[Dict[str, object]], model_dir: str) -> Dict[str, object]:
    metadata: Dict[str, object] = {"trainer": "python_fallback_nb", "models": {}}

    category = train_nb_classifier(rows, "category")
    if category.get("trained"):
        save_json_model(os.path.join(model_dir, "category_model.json"), category["payload"])  # type: ignore[arg-type]
    metadata["models"]["category"] = {k: v for k, v in category.items() if k != "payload"}

    action = train_nb_classifier(rows, "auto_action")
    if action.get("trained"):
        save_json_model(os.path.join(model_dir, "action_model.json"), action["payload"])  # type: ignore[arg-type]
    metadata["models"]["auto_action"] = {k: v for k, v in action.items() if k != "payload"}

    for key, filename in (
        ("toxicity_score", "toxicity_model.json"),
        ("hate_speech_score", "hate_model.json"),
        ("spam_score", "spam_model.json"),
    ):
        reg = train_token_mean_regressor(rows, key)
        if reg.get("trained"):
            save_json_model(os.path.join(model_dir, filename), reg["payload"])  # type: ignore[arg-type]
        metadata["models"][key] = {k: v for k, v in reg.items() if k != "payload"}

    return metadata


def build_text_feature_union(max_word_features: int = 60000, max_char_features: int = 40000) -> FeatureUnion:
    return FeatureUnion(
        transformer_list=[
            (
                "word",
                TfidfVectorizer(
                    lowercase=True,
                    strip_accents="unicode",
                    ngram_range=(1, 2),
                    max_features=max_word_features,
                    min_df=2,
                    max_df=0.99,
                ),
            ),
            (
                "char",
                TfidfVectorizer(
                    analyzer="char_wb",
                    lowercase=True,
                    ngram_range=(3, 5),
                    max_features=max_char_features,
                    min_df=2,
                    max_df=1.0,
                ),
            ),
        ]
    )


def build_text_pipeline_for_classification() -> Pipeline:
    return Pipeline(
        steps=[
            ("features", build_text_feature_union()),
            (
                "clf",
                LogisticRegression(
                    max_iter=900,
                    class_weight="balanced",
                    solver="saga",
                    random_state=RANDOM_SEED,
                    n_jobs=1,
                ),
            ),
        ]
    )


def build_text_pipeline_for_regression() -> Pipeline:
    return Pipeline(
        steps=[
            ("features", build_text_feature_union(max_word_features=50000, max_char_features=30000)),
            ("reg", Ridge(alpha=1.25)),
        ]
    )


def rows_to_dataframe(rows: List[Dict[str, object]]):
    data = {
        "text": [normalize_text(str(r.get("text", ""))) for r in rows],
        "category": [normalize_category(str(r.get("category", "general"))) for r in rows],
        "auto_action": [normalize_action(str(r.get("auto_action", "allow"))) for r in rows],
        "toxicity_score": [clamp_score(safe_int(r.get("toxicity_score", 0))) for r in rows],
        "hate_speech_score": [clamp_score(safe_int(r.get("hate_speech_score", 0))) for r in rows],
        "spam_score": [clamp_score(safe_int(r.get("spam_score", 0))) for r in rows],
    }
    df = pd.DataFrame(data)
    df = df[df["text"].str.len() > 0].copy()
    return df


def train_with_sklearn(rows: List[Dict[str, object]], model_dir: str) -> Dict[str, object]:
    assert HAS_SKLEARN
    df = rows_to_dataframe(rows)

    meta: Dict[str, object] = {"trainer": "sklearn", "models": {}, "samples": int(len(df))}
    if len(df) == 0:
        meta["status"] = "empty_dataset"
        return meta

    def train_classifier(target_col: str, out_name: str) -> Dict[str, object]:
        y = df[target_col].astype(str)
        x = df["text"].astype(str)
        if len(df) < 80 or y.nunique() < 2:
            return {"trained": False, "reason": "insufficient_rows_or_single_class", "samples": int(len(df))}

        eval_idx = df.index
        if len(df) > 30000:
            eval_idx = df.sample(n=30000, random_state=RANDOM_SEED).index
        x_eval = x.loc[eval_idx]
        y_eval = y.loc[eval_idx]

        min_class_count = int(y_eval.value_counts().min())
        folds = max(2, min(5, min_class_count))
        cv = StratifiedKFold(n_splits=folds, shuffle=True, random_state=RANDOM_SEED)
        model = build_text_pipeline_for_classification()

        y_pred_eval = cross_val_predict(model, x_eval, y_eval, cv=cv, method="predict")
        bal_acc = balanced_accuracy_score(y_eval, y_pred_eval)
        f1_macro = f1_score(y_eval, y_pred_eval, average="macro")

        model.fit(x, y)
        out_path = os.path.join(model_dir, out_name)
        joblib.dump(model, out_path)
        return {
            "trained": True,
            "samples": int(len(df)),
            "eval_samples": int(len(x_eval)),
            "classes": sorted(y.unique().tolist()),
            "balanced_accuracy_cv": round(float(bal_acc), 4),
            "f1_macro_cv": round(float(f1_macro), 4),
            "cv_folds": folds,
            "artifact": out_path,
        }

    def train_regressor(target_col: str, out_name: str) -> Dict[str, object]:
        y = pd.to_numeric(df[target_col], errors="coerce").fillna(0).clip(0, 100)
        x = df["text"].astype(str)
        if len(df) < 120 or float(y.std()) < 0.0001:
            return {"trained": False, "reason": "insufficient_rows_or_no_variance", "samples": int(len(df))}

        eval_idx = df.index
        if len(df) > 30000:
            eval_idx = df.sample(n=30000, random_state=RANDOM_SEED).index
        x_eval = x.loc[eval_idx]
        y_eval = y.loc[eval_idx]

        folds = max(2, min(5, int(len(x_eval) / 1500) if len(x_eval) >= 3000 else 3))
        cv = KFold(n_splits=folds, shuffle=True, random_state=RANDOM_SEED)
        model = build_text_pipeline_for_regression()
        y_pred_eval = cross_val_predict(model, x_eval, y_eval, cv=cv)
        mae_cv = mean_absolute_error(y_eval, y_pred_eval)

        model.fit(x, y)
        y_pred_train = model.predict(x)
        mae_train = mean_absolute_error(y, y_pred_train)

        out_path = os.path.join(model_dir, out_name)
        joblib.dump(model, out_path)
        return {
            "trained": True,
            "samples": int(len(df)),
            "eval_samples": int(len(x_eval)),
            "mae_cv": round(float(mae_cv), 4),
            "mae_train": round(float(mae_train), 4),
            "cv_folds": folds,
            "artifact": out_path,
        }

    meta["models"]["category"] = train_classifier("category", "category_model.joblib")
    meta["models"]["auto_action"] = train_classifier("auto_action", "action_model.joblib")
    meta["models"]["toxicity_score"] = train_regressor("toxicity_score", "toxicity_model.joblib")
    meta["models"]["hate_speech_score"] = train_regressor("hate_speech_score", "hate_model.joblib")
    meta["models"]["spam_score"] = train_regressor("spam_score", "spam_model.joblib")
    return meta


def main() -> int:
    if len(sys.argv) < 4:
        print(
            "Usage: python feed_ai_train.py <dataset_csv> <model_dir> <metadata_json> "
            "[augment_factor] [with_web_data] [web_max_rows] [web_languages]"
        )
        return 2

    dataset_csv = sys.argv[1]
    model_dir = sys.argv[2]
    metadata_json = sys.argv[3]
    augment_factor = safe_int(sys.argv[4], safe_int(os.getenv("FEED_AI_AUGMENT_FACTOR", "3"), 3)) if len(sys.argv) >= 5 else safe_int(os.getenv("FEED_AI_AUGMENT_FACTOR", "3"), 3)
    with_web_data = parse_bool(sys.argv[5], True) if len(sys.argv) >= 6 else parse_bool(os.getenv("FEED_AI_WITH_WEB_DATA", "1"), True)
    web_max_rows = safe_int(sys.argv[6], safe_int(os.getenv("FEED_AI_WEB_MAX_ROWS", "45000"), 45000)) if len(sys.argv) >= 7 else safe_int(os.getenv("FEED_AI_WEB_MAX_ROWS", "45000"), 45000)
    web_languages = parse_languages(sys.argv[7]) if len(sys.argv) >= 8 else parse_languages(os.getenv("FEED_AI_WEB_LANGUAGES", "fr,en,ar"))

    if not os.path.isfile(dataset_csv):
        print(f"Dataset not found: {dataset_csv}")
        return 3

    os.makedirs(model_dir, exist_ok=True)
    os.makedirs(os.path.dirname(metadata_json), exist_ok=True)

    local_rows_raw = load_local_rows(dataset_csv)
    local_rows_aug = augment_rows(local_rows_raw, augment_factor)

    web_rows: List[Dict[str, object]] = []
    web_stats: List[Dict[str, object]] = []
    if with_web_data:
        web_rows, web_stats = collect_web_rows(web_max_rows, web_languages)

    merged_rows = list(local_rows_aug) + list(web_rows)
    merged_rows = dedupe_rows(merged_rows)
    merged_rows = balance_by_label(merged_rows, "auto_action", max_ratio=6.0, min_keep=5000)
    merged_rows = balance_by_label(merged_rows, "category", max_ratio=8.0, min_keep=2200)
    if len(merged_rows) > 120000:
        merged_rows = RNG.sample(merged_rows, 120000)
    merged_rows = dedupe_rows(merged_rows)

    source_counts = dict(Counter(str(r.get("source", "unknown")) for r in merged_rows))
    write_dataset_csv(dataset_csv, merged_rows)

    metadata: Dict[str, object] = {
        "status": "ok",
        "timestamp_utc": datetime.now(timezone.utc).isoformat(),
        "dataset_rows": len(merged_rows),
        "dataset_rows_local_raw": len(local_rows_raw),
        "dataset_rows_local_augmented": len(local_rows_aug),
        "dataset_rows_web": len(web_rows),
        "augment_factor": augment_factor,
        "with_web_data": with_web_data,
        "web_max_rows_requested": web_max_rows,
        "web_languages": sorted(web_languages),
        "web_sources": web_stats,
        "source_counts": source_counts,
        "libraries": {
            "sklearn": HAS_SKLEARN,
            "datasets": HAS_HF_DATASETS,
        },
    }

    if len(merged_rows) == 0:
        metadata["status"] = "empty_dataset"
        with open(metadata_json, "w", encoding="utf-8") as f:
            json.dump(metadata, f, ensure_ascii=False, indent=2)
        print("Empty dataset. Nothing to train.")
        return 0

    if HAS_SKLEARN:
        try:
            trained = train_with_sklearn(merged_rows, model_dir)
            metadata.update(trained)
        except Exception as exc:
            metadata["sklearn_error"] = str(exc)
            fallback = train_with_fallback(merged_rows, model_dir)
            metadata.update(fallback)
            metadata["status"] = "fallback"
    else:
        fallback = train_with_fallback(merged_rows, model_dir)
        metadata.update(fallback)
        metadata["status"] = "fallback"

    with open(metadata_json, "w", encoding="utf-8") as f:
        json.dump(metadata, f, ensure_ascii=False, indent=2)

    print(json.dumps(metadata, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
