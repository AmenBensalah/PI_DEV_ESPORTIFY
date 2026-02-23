#!/usr/bin/env python3
import json
import math
import os
import re
import sys
import unicodedata
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Dict, List

try:
    import joblib  # type: ignore
except Exception:  # pragma: no cover
    joblib = None


STOP_WORDS = {
    "the", "and", "for", "avec", "dans", "pour", "mais", "donc", "alors", "des", "les", "une", "que", "qui",
    "sur", "this", "that", "from", "vous", "nous", "ils", "elles", "tout", "tous", "your", "their",
    "sans", "plus", "moins", "have", "has", "had", "are", "est", "etre", "ete", "avait",
    "http", "https", "www", "com", "depuis", "hier", "aujourd", "demain",
}

CATEGORY_KEYWORDS = {
    "tournoi": ["tournoi", "tournament", "bracket", "finale", "match", "league", "cup", "scrim"],
    "recrutement": ["recrutement", "tryout", "join team", "we need", "searching player", "lft", "lfp"],
    "resultat": ["resultat", "score", "won", "lost", "victoire", "defaite", "mvp", "classement"],
    "annonce": ["annonce", "announcement", "update", "news", "mise a jour", "nouveau"],
    "drama": ["drama", "clash", "beef", "toxic", "scandal", "controverse"],
    "event": ["event", "evenement", "lan", "bootcamp", "meetup"],
}

TOXIC_KEYWORDS = {
    "idiot": 16, "imbecile": 16, "stupide": 14, "nul": 8, "loser": 10, "degage": 14,
    "shut up": 12, "trash": 12, "noob": 8, "clown": 8, "hate": 12, "fuck": 15,
    "fck": 12, "wtf": 8, "merde": 12, "connard": 18, "pute": 18, "batard": 18,
}

HATE_KEYWORDS = {
    "raciste": 40, "racism": 40, "nazi": 50, "terroriste": 45, "sale arabe": 60,
    "sale noir": 60, "dirty arab": 60, "dirty black": 60, "go back to your country": 55,
    "genocide": 45, "kill all": 55, "hate speech": 35,
}

SPAM_KEYWORDS = {
    "buy now": 20, "promotion": 14, "promo": 12, "discount": 14, "free money": 30,
    "bitcoin": 14, "crypto": 10, "casino": 20, "bonus": 12, "click here": 18,
    "subscribe": 8, "followers": 12, "pub": 10, "publicite": 16, "offre limitee": 16,
}

TRANSLATION_PAIRS = {
    "fr_en": {
        "tournoi": "tournament", "equipe": "team", "equipes": "teams", "joueur": "player", "joueurs": "players",
        "annonce": "announcement", "recrutement": "recruitment", "resultat": "result", "victoire": "victory",
        "defaite": "defeat", "commentaire": "comment", "publication": "post", "evenement": "event",
        "bonjour": "hello", "merci": "thanks", "aujourd hui": "today",
    },
    "en_fr": {
        "tournament": "tournoi", "team": "equipe", "teams": "equipes", "player": "joueur", "players": "joueurs",
        "announcement": "annonce", "recruitment": "recrutement", "result": "resultat", "victory": "victoire",
        "defeat": "defaite", "comment": "commentaire", "post": "publication", "event": "evenement",
        "hello": "bonjour", "thanks": "merci", "today": "aujourd hui",
    },
    "fr_ar": {
        "tournoi": "\u0628\u0637\u0648\u0644\u0629", "equipe": "\u0641\u0631\u064a\u0642", "equipes": "\u0641\u0631\u0642", "joueur": "\u0644\u0627\u0639\u0628", "joueurs": "\u0644\u0627\u0639\u0628\u064a\u0646",
        "annonce": "\u0625\u0639\u0644\u0627\u0646", "recrutement": "\u0627\u0633\u062a\u0642\u0637\u0627\u0628", "resultat": "\u0646\u062a\u064a\u062c\u0629", "victoire": "\u0641\u0648\u0632",
        "defaite": "\u0647\u0632\u064a\u0645\u0629", "commentaire": "\u062a\u0639\u0644\u064a\u0642", "publication": "\u0645\u0646\u0634\u0648\u0631", "evenement": "\u062d\u062f\u062b",
        "bonjour": "\u0645\u0631\u062d\u0628\u0627", "merci": "\u0634\u0643\u0631\u0627",
    },
    "en_ar": {
        "tournament": "\u0628\u0637\u0648\u0644\u0629", "team": "\u0641\u0631\u064a\u0642", "teams": "\u0641\u0631\u0642", "player": "\u0644\u0627\u0639\u0628", "players": "\u0644\u0627\u0639\u0628\u064a\u0646",
        "announcement": "\u0625\u0639\u0644\u0627\u0646", "recruitment": "\u0627\u0633\u062a\u0642\u0637\u0627\u0628", "result": "\u0646\u062a\u064a\u062c\u0629", "victory": "\u0641\u0648\u0632",
        "defeat": "\u0647\u0632\u064a\u0645\u0629", "comment": "\u062a\u0639\u0644\u064a\u0642", "post": "\u0645\u0646\u0634\u0648\u0631", "event": "\u062d\u062f\u062b",
        "hello": "\u0645\u0631\u062d\u0628\u0627", "thanks": "\u0634\u0643\u0631\u0627",
    },
    "ar_fr": {
        "\u0628\u0637\u0648\u0644\u0629": "tournoi", "\u0641\u0631\u064a\u0642": "equipe", "\u0641\u0631\u0642": "equipes", "\u0644\u0627\u0639\u0628": "joueur", "\u0644\u0627\u0639\u0628\u064a\u0646": "joueurs",
        "\u0625\u0639\u0644\u0627\u0646": "annonce", "\u0646\u062a\u064a\u062c\u0629": "resultat", "\u0641\u0648\u0632": "victoire", "\u0647\u0632\u064a\u0645\u0629": "defaite",
        "\u062a\u0639\u0644\u064a\u0642": "commentaire", "\u0645\u0646\u0634\u0648\u0631": "publication", "\u062d\u062f\u062b": "evenement", "\u0645\u0631\u062d\u0628\u0627": "bonjour", "\u0634\u0643\u0631\u0627": "merci",
    },
    "ar_en": {
        "\u0628\u0637\u0648\u0644\u0629": "tournament", "\u0641\u0631\u064a\u0642": "team", "\u0641\u0631\u0642": "teams", "\u0644\u0627\u0639\u0628": "player", "\u0644\u0627\u0639\u0628\u064a\u0646": "players",
        "\u0625\u0639\u0644\u0627\u0646": "announcement", "\u0646\u062a\u064a\u062c\u0629": "result", "\u0641\u0648\u0632": "victory", "\u0647\u0632\u064a\u0645\u0629": "defeat",
        "\u062a\u0639\u0644\u064a\u0642": "comment", "\u0645\u0646\u0634\u0648\u0631": "post", "\u062d\u062f\u062b": "event", "\u0645\u0631\u062d\u0628\u0627": "hello", "\u0634\u0643\u0631\u0627": "thanks",
    },
}

COMMON_TRANSLATION_MAPS = {
    "fr_en": {
        "salut": "hello", "bonsoir": "good evening", "merci": "thanks", "svp": "please",
        "s il vous plait": "please", "nous": "we", "vous": "you", "ils": "they",
        "je": "i", "tu": "you", "on": "we", "dans": "in", "avec": "with", "sans": "without",
        "pour": "for", "contre": "against", "avant": "before", "apres": "after",
        "maintenant": "now", "aujourd hui": "today", "demain": "tomorrow", "hier": "yesterday", "ce": "this", "a": "at",
        "event": "event", "evenement": "event", "inscription": "registration", "inscrivez": "register",
        "finale": "final", "match": "match", "victoire": "victory", "defaite": "defeat",
        "resultat": "result", "score": "score", "equipe": "team", "joueur": "player",
        "recrutement": "recruitment", "annonce": "announcement", "commentaire": "comment",
        "publication": "post", "serveur": "server", "discord": "discord", "lien": "link", "weekend": "weekend",
        "ouvert": "open", "rejoignez": "join", "nous rejoindre": "join us",
        "horaire": "schedule", "heure": "time", "region": "region", "niveau": "level",
        "tous": "all", "toutes": "all", "les": "the", "des": "some", "du": "of the", "de": "of",
        "ouvert": "open", "ouverte": "open", "ouverts": "open", "ouvertes": "open",
    },
    "en_fr": {
        "hello": "bonjour", "hi": "salut", "thanks": "merci", "please": "svp",
        "we": "nous", "you": "vous", "they": "ils", "i": "je", "in": "dans",
        "with": "avec", "without": "sans", "for": "pour", "against": "contre",
        "before": "avant", "after": "apres", "now": "maintenant", "today": "aujourd hui",
        "tomorrow": "demain", "yesterday": "hier", "this": "ce", "at": "a", "event": "evenement", "registration": "inscription",
        "register": "inscrivez", "final": "finale", "match": "match", "victory": "victoire",
        "defeat": "defaite", "result": "resultat", "score": "score", "team": "equipe",
        "player": "joueur", "recruitment": "recrutement", "announcement": "annonce",
        "comment": "commentaire", "post": "publication", "server": "serveur", "link": "lien",
        "schedule": "horaire", "time": "heure", "region": "region", "level": "niveau", "open": "ouvert",
        "join": "rejoignez", "us": "nous", "weekend": "week-end",
    },
    "fr_ar": {
        "bonjour": "\u0645\u0631\u062d\u0628\u0627", "salut": "\u0627\u0647\u0644\u0627", "merci": "\u0634\u0643\u0631\u0627", "svp": "\u0645\u0646 \u0641\u0636\u0644\u0643",
        "nous": "\u0646\u062d\u0646", "vous": "\u0627\u0646\u062a\u0645", "je": "\u0627\u0646\u0627", "tu": "\u0627\u0646\u062a",
        "avec": "\u0645\u0639", "sans": "\u0628\u062f\u0648\u0646", "pour": "\u0644", "contre": "\u0636\u062f",
        "maintenant": "\u0627\u0644\u0627\u0646", "aujourd hui": "\u0627\u0644\u064a\u0648\u0645", "demain": "\u063a\u062f\u0627", "hier": "\u0627\u0645\u0633",
        "tournoi": "\u0628\u0637\u0648\u0644\u0629", "equipe": "\u0641\u0631\u064a\u0642", "joueur": "\u0644\u0627\u0639\u0628", "finale": "\u0646\u0647\u0627\u0626\u064a",
        "match": "\u0645\u0628\u0627\u0631\u0627\u0629", "victoire": "\u0641\u0648\u0632", "defaite": "\u0647\u0632\u064a\u0645\u0629",
        "resultat": "\u0646\u062a\u064a\u062c\u0629", "annonce": "\u0627\u0639\u0644\u0627\u0646", "recrutement": "\u0627\u0633\u062a\u0642\u0637\u0627\u0628",
        "commentaire": "\u062a\u0639\u0644\u064a\u0642", "publication": "\u0645\u0646\u0634\u0648\u0631", "lien": "\u0631\u0627\u0628\u0637",
        "heure": "\u0627\u0644\u0648\u0642\u062a", "region": "\u0645\u0646\u0637\u0642\u0629", "niveau": "\u0645\u0633\u062a\u0648\u0649",
    },
    "en_ar": {
        "hello": "\u0645\u0631\u062d\u0628\u0627", "hi": "\u0627\u0647\u0644\u0627", "thanks": "\u0634\u0643\u0631\u0627", "please": "\u0645\u0646 \u0641\u0636\u0644\u0643",
        "we": "\u0646\u062d\u0646", "you": "\u0627\u0646\u062a\u0645", "i": "\u0627\u0646\u0627",
        "with": "\u0645\u0639", "without": "\u0628\u062f\u0648\u0646", "for": "\u0644", "against": "\u0636\u062f",
        "now": "\u0627\u0644\u0627\u0646", "today": "\u0627\u0644\u064a\u0648\u0645", "tomorrow": "\u063a\u062f\u0627", "yesterday": "\u0627\u0645\u0633",
        "tournament": "\u0628\u0637\u0648\u0644\u0629", "team": "\u0641\u0631\u064a\u0642", "player": "\u0644\u0627\u0639\u0628", "final": "\u0646\u0647\u0627\u0626\u064a",
        "match": "\u0645\u0628\u0627\u0631\u0627\u0629", "victory": "\u0641\u0648\u0632", "defeat": "\u0647\u0632\u064a\u0645\u0629",
        "result": "\u0646\u062a\u064a\u062c\u0629", "announcement": "\u0627\u0639\u0644\u0627\u0646", "recruitment": "\u0627\u0633\u062a\u0642\u0637\u0627\u0628",
        "comment": "\u062a\u0639\u0644\u064a\u0642", "post": "\u0645\u0646\u0634\u0648\u0631", "link": "\u0631\u0627\u0628\u0637",
        "time": "\u0627\u0644\u0648\u0642\u062a", "region": "\u0645\u0646\u0637\u0642\u0629", "level": "\u0645\u0633\u062a\u0648\u0649",
    },
    "ar_fr": {
        "\u0645\u0631\u062d\u0628\u0627": "bonjour", "\u0627\u0647\u0644\u0627": "salut", "\u0634\u0643\u0631\u0627": "merci", "\u0646\u062d\u0646": "nous",
        "\u0627\u0646\u062a\u0645": "vous", "\u0627\u0646\u0627": "je", "\u0645\u0639": "avec", "\u0628\u062f\u0648\u0646": "sans",
        "\u0627\u0644\u0627\u0646": "maintenant", "\u0627\u0644\u064a\u0648\u0645": "aujourd hui", "\u063a\u062f\u0627": "demain", "\u0627\u0645\u0633": "hier",
        "\u0628\u0637\u0648\u0644\u0629": "tournoi", "\u0641\u0631\u064a\u0642": "equipe", "\u0644\u0627\u0639\u0628": "joueur", "\u0646\u0647\u0627\u0626\u064a": "finale",
        "\u0645\u0628\u0627\u0631\u0627\u0629": "match", "\u0641\u0648\u0632": "victoire", "\u0647\u0632\u064a\u0645\u0629": "defaite",
        "\u0646\u062a\u064a\u062c\u0629": "resultat", "\u0627\u0639\u0644\u0627\u0646": "annonce", "\u0627\u0633\u062a\u0642\u0637\u0627\u0628": "recrutement",
        "\u062a\u0639\u0644\u064a\u0642": "commentaire", "\u0645\u0646\u0634\u0648\u0631": "publication", "\u0631\u0627\u0628\u0637": "lien",
    },
    "ar_en": {
        "\u0645\u0631\u062d\u0628\u0627": "hello", "\u0627\u0647\u0644\u0627": "hi", "\u0634\u0643\u0631\u0627": "thanks", "\u0646\u062d\u0646": "we",
        "\u0627\u0646\u062a\u0645": "you", "\u0627\u0646\u0627": "i", "\u0645\u0639": "with", "\u0628\u062f\u0648\u0646": "without",
        "\u0627\u0644\u0627\u0646": "now", "\u0627\u0644\u064a\u0648\u0645": "today", "\u063a\u062f\u0627": "tomorrow", "\u0627\u0645\u0633": "yesterday",
        "\u0628\u0637\u0648\u0644\u0629": "tournament", "\u0641\u0631\u064a\u0642": "team", "\u0644\u0627\u0639\u0628": "player", "\u0646\u0647\u0627\u0626\u064a": "final",
        "\u0645\u0628\u0627\u0631\u0627\u0629": "match", "\u0641\u0648\u0632": "victory", "\u0647\u0632\u064a\u0645\u0629": "defeat",
        "\u0646\u062a\u064a\u062c\u0629": "result", "\u0627\u0639\u0644\u0627\u0646": "announcement", "\u0627\u0633\u062a\u0642\u0637\u0627\u0628": "recruitment",
        "\u062a\u0639\u0644\u064a\u0642": "comment", "\u0645\u0646\u0634\u0648\u0631": "post", "\u0631\u0627\u0628\u0637": "link",
    },
}

MODEL_DIR = os.path.join(os.path.dirname(__file__), "..", "..", "var", "feed_ai", "models")
_MODEL_CACHE: Dict[str, object] = {}
_WEB_CACHE: Dict[str, object] = {}

MOJIBAKE_FIXES = {
    "Ã©": "é", "Ã¨": "è", "Ãª": "ê", "Ã«": "ë", "Ã ": "à", "Ã¢": "â", "Ã®": "î", "Ã¯": "ï",
    "Ã´": "ô", "Ã¶": "ö", "Ã¹": "ù", "Ã»": "û", "Ã¼": "ü", "Ã§": "ç",
    "Ã‰": "É", "Ã€": "À", "Ã‡": "Ç",
    "â€™": "'", "â€˜": "'", "â€œ": '"', "â€": '"', "â€¦": "...", "â€“": "-", "â€”": "-",
}

def normalize_text(text: str) -> str:
    cleaned = str(text or "")
    for broken, good in MOJIBAKE_FIXES.items():
        cleaned = cleaned.replace(broken, good)
    cleaned = re.sub(r"\s+", " ", cleaned.strip())
    return cleaned


def detect_language(text: str) -> str:
    if re.search(r"[\u0600-\u06FF]", text or ""):
        return "ar"
    lower = f" {(text or '').lower()} "
    fr_markers = [" le ", " la ", " les ", " des ", " est ", " une ", " equipe", " tournoi", "bonjour", "merci", " je ", " nous ", " vous "]
    en_markers = [" the ", " and ", " is ", " are ", " team", " tournament", "hello", "thanks"]
    fr_score = sum(1 for marker in fr_markers if marker in lower)
    en_score = sum(1 for marker in en_markers if marker in lower)
    return "en" if en_score > fr_score else "fr"


def normalize_lookup_key(value: str) -> str:
    normalized = unicodedata.normalize("NFKD", value or "")
    normalized = "".join(ch for ch in normalized if not unicodedata.combining(ch))
    normalized = normalized.replace("'", " ").replace("’", " ")
    normalized = re.sub(r"\s+", " ", normalized.strip().lower())
    return normalized


def apply_dictionary_tokens(text: str, mapping: Dict[str, str]) -> str:
    if not mapping:
        return text
    out_parts: List[str] = []
    parts = re.split(r"(\s+|[.,!?;:()\[\]{}\"'`])", text)
    for part in parts:
        if part == "" or re.fullmatch(r"\s+|[.,!?;:()\[\]{}\"'`]", part):
            out_parts.append(part)
            continue
        key = normalize_lookup_key(part)
        repl = mapping.get(key)
        if repl is None:
            out_parts.append(part)
            continue
        if part[:1].isupper():
            repl = repl[:1].upper() + repl[1:] if repl else repl
        out_parts.append(repl)
    return "".join(out_parts)


def summarize_text(text: str, max_chars: int) -> str:
    value = polish_text(normalize_text(text))
    if len(value) <= max_chars:
        return value
    snippets = re.split(r"(?<=[.!?])\s+", value)
    out = ""
    for sentence in snippets:
        candidate = (out + " " + sentence).strip()
        if len(candidate) > max_chars:
            break
        out = candidate
    if out:
        return out
    return (value[: max(0, max_chars - 3)] + "...").strip()


def tokenize(text: str) -> List[str]:
    return [t for t in re.split(r"[^a-z0-9\u0600-\u06FF]+", (text or "").lower()) if t]


def categorize(text: str) -> str:
    lower = (text or "").lower()
    best = ("general", 0)
    for category, words in CATEGORY_KEYWORDS.items():
        score = 0
        for w in words:
            if w in lower:
                score += 1
        if score > best[1]:
            best = (category, score)
    return best[0]


def normalize_hashtags(values: List[str]) -> List[str]:
    out: List[str] = []
    seen = set()
    for value in values:
        if not isinstance(value, str):
            continue
        v = value.strip().lower()
        if not v:
            continue
        if not v.startswith("#"):
            v = "#" + v
        v = re.sub(r"[^#a-z0-9\u0600-\u06FF_]", "", v)
        if len(v) <= 1 or v in seen:
            continue
        seen.add(v)
        out.append(v)
        if len(out) >= 8:
            break
    return out


def generate_hashtags(text: str, category: str = "general") -> List[str]:
    tokens = tokenize(text)
    freq: Dict[str, int] = {}
    for token in tokens:
        if len(token) < 3 or token in STOP_WORDS:
            continue
        freq[token] = freq.get(token, 0) + 1
    ranked = sorted(freq.items(), key=lambda x: (-x[1], x[0]))
    tags = []
    if category and category != "general":
        tags.append("#" + category)
    tags.extend("#" + token for token, _ in ranked[:7])
    return normalize_hashtags(tags)


def rewrite(text: str, mode: str) -> str:
    value = polish_text(normalize_text(text))
    if not value:
        return ""
    mode = (mode or "pro").strip().lower()
    if mode in ("correct", "pro"):
        return polish_text(value)
    if mode == "short":
        return summarize_text(value, 180)
    if mode == "long":
        base = value if re.search(r"[.!?]$", value) else value + "."
        if len(tokenize(value)) <= 8:
            web_info = web_enrich(value)
            if bool(web_info.get("used")):
                hint = summarize_text(str(web_info.get("summary", "")), 120)
                if hint:
                    base = f"{base} Contexte: {hint}"
        return polish_text(base + "\n\nN'hesitez pas a donner votre avis et partager vos retours en commentaire.")
    return value


def polish_text(text: str) -> str:
    value = normalize_text(text)
    if not value:
        return ""
    shorthand = {
        r"\bslt\b": "salut",
        r"\bbjr\b": "bonjour",
        r"\bstp\b": "s'il te plait",
        r"\bsvp\b": "s'il vous plait",
        r"\bpk\b": "pourquoi",
    }
    for pattern, repl in shorthand.items():
        value = re.sub(pattern, repl, value, flags=re.IGNORECASE)
    value = re.sub(r"\s+([,.;:!?])", r"\1", value)
    value = re.sub(r"([,.;:!?])([^\s])", r"\1 \2", value)
    value = re.sub(r"\s{2,}", " ", value).strip()
    if value:
        value = value[0].upper() + value[1:]
    if value and not re.search(r"[.!?]$", value):
        value += "."
    return value


def _web_cache_path() -> str:
    return str(Path(__file__).resolve().parents[2] / "var" / "feed_ai" / "web_cache.json")


def _load_web_cache() -> Dict[str, object]:
    global _WEB_CACHE
    if _WEB_CACHE:
        return _WEB_CACHE
    path = _web_cache_path()
    try:
        with open(path, "r", encoding="utf-8") as f:
            payload = json.load(f)
        _WEB_CACHE = payload if isinstance(payload, dict) else {}
    except Exception:
        _WEB_CACHE = {}
    return _WEB_CACHE


def _save_web_cache() -> None:
    cache = _load_web_cache()
    path = _web_cache_path()
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(cache, f, ensure_ascii=False)


def _fetch_json(url: str, timeout: float = 2.5) -> Dict:
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": "EsportifyFeedAI/1.0 (+local)",
            "Accept": "application/json",
        },
    )
    with urllib.request.urlopen(req, timeout=timeout) as resp:  # nosec B310
        raw = resp.read().decode("utf-8", errors="ignore")
    data = json.loads(raw)
    return data if isinstance(data, dict) else {}


def _extract_query_terms(text: str, max_terms: int = 4) -> List[str]:
    tokens = [t for t in tokenize(text) if len(t) >= 4 and t not in STOP_WORDS]
    seen = set()
    out: List[str] = []
    for token in tokens:
        if token in seen:
            continue
        seen.add(token)
        out.append(token)
        if len(out) >= max_terms:
            break
    return out


def web_enrich(text: str) -> Dict[str, object]:
    allow_web = os.getenv("FEED_AI_ALLOW_WEB", "0").strip().lower() in {"1", "true", "yes", "on"}
    if not allow_web:
        return {"used": False, "reason": "disabled"}

    query_terms = _extract_query_terms(text)
    if not query_terms:
        return {"used": False, "reason": "no_terms"}

    query = " ".join(query_terms)
    cache_key = normalize_lookup_key(query)
    cache = _load_web_cache()
    cached = cache.get(cache_key)
    if isinstance(cached, dict) and isinstance(cached.get("summary"), str) and cached.get("summary"):
        return {"used": True, "source": "cache", **cached}

    lang = "fr" if detect_language(text) == "fr" else "en"
    encoded = urllib.parse.quote(query)
    try:
        search_url = f"https://{lang}.wikipedia.org/w/api.php?action=opensearch&search={encoded}&limit=1&namespace=0&format=json"
        with urllib.request.urlopen(search_url, timeout=2.5) as resp:  # nosec B310
            arr_raw = resp.read().decode("utf-8", errors="ignore")
        arr = json.loads(arr_raw)
        if not isinstance(arr, list) or len(arr) < 2 or not isinstance(arr[1], list) or not arr[1]:
            return {"used": False, "reason": "no_hit"}

        title = str(arr[1][0]).strip()
        if not title:
            return {"used": False, "reason": "no_title"}

        title_encoded = urllib.parse.quote(title.replace(" ", "_"))
        summary_url = f"https://{lang}.wikipedia.org/api/rest_v1/page/summary/{title_encoded}"
        summary_obj = _fetch_json(summary_url, timeout=2.8)
        summary = normalize_text(str(summary_obj.get("extract", "")))
        summary = summarize_text(summary, 220)
        if summary == "":
            return {"used": False, "reason": "empty_summary"}

        payload = {
            "query": query,
            "title": title,
            "summary": summary,
            "url": f"https://{lang}.wikipedia.org/wiki/{title_encoded}",
            "lang": lang,
        }
        cache[cache_key] = payload
        # Keep cache bounded.
        if len(cache) > 300:
            for key in list(cache.keys())[:120]:
                cache.pop(key, None)
        _save_web_cache()
        return {"used": True, "source": "wikipedia", **payload}
    except Exception:
        return {"used": False, "reason": "fetch_failed"}


def translate(text: str, target: str) -> str:
    source_text = normalize_text(text)
    if not source_text:
        return ""
    target = (target or "").strip().lower()
    if target not in ("fr", "en", "ar"):
        return source_text
    source = detect_language(source_text)
    if source == target:
        return source_text
    key = f"{source}_{target}"
    phrase_mapping = TRANSLATION_PAIRS.get(key, {})
    token_mapping = COMMON_TRANSLATION_MAPS.get(key, {})
    if not phrase_mapping and not token_mapping:
        return source_text

    translated = source_text
    for frm, to in phrase_mapping.items():
        pattern = rf"(?<!\w){re.escape(frm)}(?!\w)"
        translated = re.sub(pattern, to, translated, flags=re.IGNORECASE)
    translated = apply_dictionary_tokens(translated, token_mapping)
    translated = normalize_text(translated)
    if not translated or translated.lower() == source_text.lower():
        return source_text
    if target == "ar":
        return translated
    return polish_text(translated)


def enrich_analysis(payload: Dict) -> Dict:
    text = normalize_text(str(payload.get("text", "")))
    category = categorize(text)
    hashtags = generate_hashtags(text, category)
    return {
        "summary_short": summarize_text(text, 180),
        "summary_long": summarize_text(text, 360),
        "category": category,
        "hashtags": hashtags,
    }


def score_keywords(text: str, keywords: Dict[str, int]) -> int:
    score = 0
    lower = (text or "").lower()
    for word, weight in keywords.items():
        if word in lower:
            score += int(weight)
    return score


def clamp_score(value: int) -> int:
    if value < 0:
        return 0
    if value > 100:
        return 100
    return int(value)


def compute_duplicate_score(text: str, existing_texts: List[str]) -> int:
    value = normalize_text(text).lower()
    if not value or not existing_texts:
        return 0
    tokens = set(tokenize(value))
    if not tokens:
        return 0
    best = 0.0
    for existing in existing_texts:
        cand = set(tokenize(str(existing).lower()))
        if not cand:
            continue
        inter = len(tokens.intersection(cand))
        union = len(tokens.union(cand)) or 1
        ratio = inter / union
        if ratio > best:
            best = ratio
    return clamp_score(round(best * 100))


def compute_media_risk(media_paths: List[str]) -> int:
    risk = 0
    for path in media_paths:
        lower = str(path or "").lower()
        if not lower:
            continue
        if re.search(r"(nsfw|xxx|gore|blood|weapon|violence|hate)", lower):
            risk += 30
        if re.search(r"\.(exe|scr|bat|cmd|js)(\?.*)?$", lower):
            risk += 40
        if re.search(r"\.(zip|rar|7z)(\?.*)?$", lower):
            risk += 20
    return clamp_score(risk)


def compute_caps_ratio(text: str) -> float:
    letters = [c for c in text if c.isalpha()]
    if not letters:
        return 0.0
    upper = sum(1 for c in letters if c.isupper())
    return upper / max(1, len(letters))


def action_severity(action: str) -> int:
    mapping = {"allow": 0, "review": 1, "block": 2}
    return mapping.get((action or "").strip().lower(), 0)


def _load_json(path: str) -> Dict:
    try:
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)
        return data if isinstance(data, dict) else {}
    except Exception:
        return {}


def load_models() -> Dict[str, object]:
    global _MODEL_CACHE
    if _MODEL_CACHE:
        return _MODEL_CACHE
    if not os.path.isdir(MODEL_DIR):
        _MODEL_CACHE = {}
        return _MODEL_CACHE

    model_files = {
        "category": "category_model.joblib",
        "auto_action": "action_model.joblib",
        "toxicity_score": "toxicity_model.joblib",
        "hate_speech_score": "hate_model.joblib",
        "spam_score": "spam_model.joblib",
    }
    loaded: Dict[str, object] = {}
    if joblib is not None:
        for key, filename in model_files.items():
            path = os.path.join(MODEL_DIR, filename)
            if not os.path.isfile(path):
                continue
            try:
                loaded[key] = joblib.load(path)
            except Exception:
                continue

    json_files = {
        "category_json": "category_model.json",
        "auto_action_json": "action_model.json",
        "toxicity_score_json": "toxicity_model.json",
        "hate_speech_score_json": "hate_model.json",
        "spam_score_json": "spam_model.json",
    }
    for key, filename in json_files.items():
        path = os.path.join(MODEL_DIR, filename)
        if os.path.isfile(path):
            payload = _load_json(path)
            if payload:
                loaded[key] = payload

    _MODEL_CACHE = loaded
    return _MODEL_CACHE


def _predict_nb_label(model: Dict, text: str) -> str:
    classes = [str(c) for c in model.get("classes", [])]
    class_counts = model.get("class_counts", {})
    token_counts = model.get("token_counts", {})
    total_tokens = model.get("total_tokens", {})
    vocab_size = max(1, int(model.get("vocab_size", 1)))
    if not classes or not isinstance(class_counts, dict) or not isinstance(token_counts, dict):
        return ""

    total_docs = max(1, sum(int(class_counts.get(c, 0)) for c in classes))
    toks = tokenize(text)
    if not toks:
        return classes[0]

    best_label = classes[0]
    best_logp = -10**18
    for c in classes:
        prior = (int(class_counts.get(c, 0)) + 1) / (total_docs + len(classes))
        logp = math.log(prior)
        cls_token_map = token_counts.get(c, {})
        cls_total = max(1, int(total_tokens.get(c, 0)))
        for t in toks:
            tok_count = int(cls_token_map.get(t, 0)) if isinstance(cls_token_map, dict) else 0
            prob = (tok_count + 1) / (cls_total + vocab_size)
            logp += math.log(prob)
        if logp > best_logp:
            best_logp = logp
            best_label = c
    return best_label


def _predict_token_mean(model: Dict, text: str) -> int:
    global_mean = float(model.get("global_mean", 0.0))
    token_means = model.get("token_means", {})
    if not isinstance(token_means, dict):
        return clamp_score(round(global_mean))
    vals = []
    for t in set(tokenize(text)):
        if t in token_means:
            try:
                vals.append(float(token_means[t]))
            except Exception:
                continue
    if not vals:
        return clamp_score(round(global_mean))
    return clamp_score(round((0.55 * global_mean) + (0.45 * (sum(vals) / len(vals)))))


def predict_with_models(text: str) -> Dict[str, object]:
    models = load_models()
    if not models:
        return {}
    one = [text]
    pred: Dict[str, object] = {}

    category_model = models.get("category")
    if category_model is not None:
        try:
            pred["category"] = str(category_model.predict(one)[0]).strip().lower()
        except Exception:
            pass
    category_json = models.get("category_json")
    if isinstance(category_json, dict):
        value = _predict_nb_label(category_json, text)
        if value:
            pred["category"] = value

    action_model = models.get("auto_action")
    if action_model is not None:
        try:
            pred["auto_action"] = str(action_model.predict(one)[0]).strip().lower()
        except Exception:
            pass
    action_json = models.get("auto_action_json")
    if isinstance(action_json, dict):
        value = _predict_nb_label(action_json, text)
        if value:
            pred["auto_action"] = value

    for score_key in ("toxicity_score", "hate_speech_score", "spam_score"):
        model = models.get(score_key)
        if model is None:
            continue
        try:
            value = float(model.predict(one)[0])
            pred[score_key] = clamp_score(round(value))
        except Exception:
            continue

    for score_key in ("toxicity_score", "hate_speech_score", "spam_score"):
        model = models.get(score_key + "_json")
        if isinstance(model, dict):
            pred[score_key] = _predict_token_mean(model, text)

    return pred


def decide_auto_action(toxicity: int, hate: int, spam: int, duplicate: int, media_risk: int) -> str:
    # Professional moderation: lower tolerance for explicit hate/harassment.
    if hate >= 45 or toxicity >= 72 or spam >= 85 or media_risk >= 75:
        return "block"
    if hate >= 30 or toxicity >= 48 or spam >= 55 or duplicate >= 78 or media_risk >= 48:
        return "review"
    return "allow"


def build_flags(toxicity: int, hate: int, spam: int, duplicate: int, media_risk: int, auto_action: str) -> List[str]:
    flags: List[str] = []
    if toxicity >= 45:
        flags.append("toxicity")
    if hate >= 30:
        flags.append("hate_speech")
    if spam >= 40:
        flags.append("spam")
    if duplicate >= 75:
        flags.append("duplicate")
    if media_risk >= 40:
        flags.append("media_risk")
    if auto_action == "review":
        flags.append("needs_review")
    if auto_action == "block":
        flags.append("blocked_auto")
    return flags


def build_risk_label(auto_action: str, toxicity: int, spam: int, hate: int) -> str:
    if auto_action == "block":
        return "critique"
    if auto_action == "review":
        return "eleve"
    if max(toxicity, spam, hate) >= 35:
        return "modere"
    return "faible"


def build_block_reason_and_tip(
    toxicity: int,
    hate: int,
    spam: int,
    duplicate: int,
    media_risk: int,
    auto_action: str,
) -> Dict[str, str]:
    if auto_action == "allow":
        return {
            "block_reason": "Aucun signal critique detecte.",
            "blocking_tip": "Aucune action requise.",
        }

    score_pairs = [
        ("hate", hate),
        ("toxicity", toxicity),
        ("spam", spam),
        ("duplicate", duplicate),
        ("media", media_risk),
    ]
    score_pairs.sort(key=lambda x: x[1], reverse=True)
    main, score = score_pairs[0]

    if main == "hate":
        reason = f"Discours haineux suspect detecte (score {score}/100)."
        tip = "Retirer toute formulation discriminatoire et reformuler le message."
    elif main == "toxicity":
        reason = f"Toxicite/agressivite elevee detectee (score {score}/100)."
        tip = "Supprimer insultes et attaques personnelles."
    elif main == "spam":
        reason = f"Comportement de spam/promotion excessive detecte (score {score}/100)."
        tip = "Reduire liens repetes/publicite et publier un contenu utile."
    elif main == "duplicate":
        reason = f"Doublon probable detecte (score {score}/100)."
        tip = "Modifier le texte pour apporter une information nouvelle."
    else:
        reason = f"Media potentiellement non conforme detecte (score {score}/100)."
        tip = "Remplacer le media par un fichier propre et adapte."

    if auto_action == "review":
        reason = "Alerte moderation: " + reason
        tip = "Verification manuelle recommandee. " + tip

    return {"block_reason": reason, "blocking_tip": tip}


def analyze_full(payload: Dict) -> Dict:
    text = normalize_text(str(payload.get("text", "")))
    lower = text.lower()
    existing_texts = payload.get("existing_texts", [])
    media_paths = payload.get("media_paths", [])
    if not isinstance(existing_texts, list):
        existing_texts = []
    if not isinstance(media_paths, list):
        media_paths = []

    toxicity_score = score_keywords(lower, TOXIC_KEYWORDS)
    hate_speech_score = score_keywords(lower, HATE_KEYWORDS)
    spam_score = score_keywords(lower, SPAM_KEYWORDS)
    duplicate_score = compute_duplicate_score(text, [str(x) for x in existing_texts])
    media_risk_score = compute_media_risk([str(x) for x in media_paths])

    links_count = len(re.findall(r"https?://", text, flags=re.IGNORECASE))
    if links_count >= 2:
        spam_score += 18
    if links_count >= 4:
        spam_score += 22
    if re.search(r"(.)\1{5,}", text):
        spam_score += 18

    caps_ratio = compute_caps_ratio(text)
    if caps_ratio > 0.45:
        spam_score += 12
        toxicity_score += 6

    if 0 < len(text) < 14 and links_count > 0:
        spam_score += 16

    category = categorize(lower)
    hashtags = generate_hashtags(text, category)
    summary_short = summarize_text(text, 160)
    summary_long = summarize_text(text, 320)
    web_info: Dict[str, object] = {"used": False}
    if bool(payload.get("with_ai", False)) and len(tokenize(text)) <= 9:
        web_info = web_enrich(text)
        if bool(web_info.get("used")):
            web_summary = normalize_text(str(web_info.get("summary", "")))
            if web_summary:
                # Keep user's original text as primary; add brief factual context.
                summary_long = summarize_text(f"{text} {web_summary}", 320)
                summary_short = summarize_text(f"{text} {web_summary}", 160)
                hashtags = normalize_hashtags(hashtags + generate_hashtags(web_summary, category))

    toxicity_score = clamp_score(toxicity_score)
    hate_speech_score = clamp_score(hate_speech_score)
    spam_score = clamp_score(spam_score)
    duplicate_score = clamp_score(duplicate_score)
    media_risk_score = clamp_score(media_risk_score)

    trained = predict_with_models(text)
    if trained:
        trained_category = str(trained.get("category", "")).strip().lower()
        if trained_category:
            category = trained_category

        if "toxicity_score" in trained:
            toxicity_score = clamp_score(round((0.58 * toxicity_score) + (0.42 * int(trained["toxicity_score"]))))
        if "hate_speech_score" in trained:
            hate_speech_score = clamp_score(round((0.58 * hate_speech_score) + (0.42 * int(trained["hate_speech_score"]))))
        if "spam_score" in trained:
            spam_score = clamp_score(round((0.58 * spam_score) + (0.42 * int(trained["spam_score"]))))

    hashtags = generate_hashtags(text, category)

    auto_action = decide_auto_action(toxicity_score, hate_speech_score, spam_score, duplicate_score, media_risk_score)
    trained_action = str(trained.get("auto_action", "")).strip().lower() if trained else ""
    if trained_action in ("allow", "review", "block"):
        current_severity = action_severity(auto_action)
        trained_severity = action_severity(trained_action)
        # Keep moderation conservative: only accept strong escalation if scores support it.
        if trained_severity > current_severity:
            risk_max = max(toxicity_score, hate_speech_score, spam_score, media_risk_score)
            if trained_action == "block":
                if current_severity >= 1 or risk_max >= 45:
                    auto_action = trained_action
            elif trained_action == "review":
                if risk_max >= 22:
                    auto_action = trained_action
        elif trained_severity < current_severity:
            # Allow only a one-step de-escalation when risks are low.
            risk_max = max(toxicity_score, hate_speech_score, spam_score, media_risk_score)
            if current_severity - trained_severity == 1 and risk_max <= 28:
                auto_action = trained_action

    flags = build_flags(toxicity_score, hate_speech_score, spam_score, duplicate_score, media_risk_score, auto_action)
    risk_label = build_risk_label(auto_action, toxicity_score, spam_score, hate_speech_score)
    moderation_explain = build_block_reason_and_tip(
        toxicity_score,
        hate_speech_score,
        spam_score,
        duplicate_score,
        media_risk_score,
        auto_action,
    )

    return {
        "summary_short": polish_text(summary_short),
        "summary_long": polish_text(summary_long),
        "hashtags": hashtags,
        "category": category,
        "toxicity_score": toxicity_score,
        "hate_speech_score": hate_speech_score,
        "spam_score": spam_score,
        "duplicate_score": duplicate_score,
        "media_risk_score": media_risk_score,
        "auto_action": auto_action,
        "flags": flags,
        "risk_label": risk_label,
        "block_reason": moderation_explain["block_reason"],
        "blocking_tip": moderation_explain["blocking_tip"],
        "web_context": web_info,
    }


def main() -> int:
    if len(sys.argv) < 4:
        return 2
    _, task, input_path, output_path = sys.argv[0:4]
    if not os.path.isfile(input_path):
        return 3

    with open(input_path, "r", encoding="utf-8-sig") as fh:
        payload = json.load(fh)

    result: Dict = {}
    if task == "hashtags":
        text = str(payload.get("text", ""))
        options = payload.get("options", {}) if isinstance(payload.get("options", {}), dict) else {}
        category = str(options.get("category", "")) or categorize(text)
        result = {"hashtags": generate_hashtags(text, category)}
    elif task == "rewrite":
        result = {
            "output": rewrite(str(payload.get("text", "")), str(payload.get("mode", "pro"))),
            "mode": str(payload.get("mode", "pro")),
        }
    elif task == "translate":
        result = {
            "translated": translate(str(payload.get("text", "")), str(payload.get("target", "en"))),
            "target": str(payload.get("target", "en")).lower(),
        }
    elif task == "enrich_analysis":
        result = enrich_analysis(payload if isinstance(payload, dict) else {})
    elif task == "analyze_full":
        result = analyze_full(payload if isinstance(payload, dict) else {})
    else:
        result = {"error": "unknown_task"}

    with open(output_path, "w", encoding="utf-8") as fh:
        json.dump(result, fh, ensure_ascii=False)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
