#!/usr/bin/env python3
"""Build lang/{locale}.json from en.json via Google Translate."""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from pathlib import Path

try:
    from deep_translator import GoogleTranslator
except ImportError:
    print("Use .venv-translate/bin/python after: pip install deep-translator", file=sys.stderr)
    sys.exit(1)

BASE = Path(__file__).resolve().parent.parent
EN_PATH = BASE / "en.json"
BATCH = 40
SLEEP = 0.35

PLACEHOLDER_RE = re.compile(r"(:[a-zA-Z_][a-zA-Z0-9_]*)|(\{\{[^}]+\}\})")


def protect(text: str) -> tuple[str, list[str]]:
    tokens: list[str] = []

    def repl(m: re.Match[str]) -> str:
        tokens.append(m.group(0))
        return f"__PH{len(tokens) - 1}__"

    return PLACEHOLDER_RE.sub(repl, text), tokens


def restore(text: str, tokens: list[str]) -> str:
    for i, tok in enumerate(tokens):
        text = text.replace(f"__PH{i}__", tok)
    return text


def translate_value(value: str, translator: GoogleTranslator) -> str:
    if not value.strip():
        return value
    protected, tokens = protect(value)
    try:
        out = translator.translate(protected)
    except Exception as exc:  # noqa: BLE001
        print(f"  warn: {exc!r} for {value[:60]!r}", file=sys.stderr)
        return value
    return restore(out, tokens)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("locale", help="Locale code, e.g. hi or id")
    parser.add_argument(
        "google_target",
        nargs="?",
        help="Google Translate target code (defaults to locale)",
    )
    args = parser.parse_args()
    google_target = args.google_target or args.locale
    out_path = BASE / f"{args.locale}.json"

    en: dict[str, str] = json.loads(EN_PATH.read_text(encoding="utf-8"))
    existing: dict[str, str] = {}
    if out_path.exists():
        existing = json.loads(out_path.read_text(encoding="utf-8"))

    translator = GoogleTranslator(source="en", target=google_target)
    keys = sorted(en.keys())
    total = len(keys)
    data: dict[str, str] = dict(existing)

    for start in range(0, total, BATCH):
        for key in keys[start : start + BATCH]:
            if key in data and data[key] != en[key]:
                continue
            src = en[key]
            if src == key:
                data[key] = key
                continue
            data[key] = translate_value(src, translator)
        print(f"Translated {min(start + BATCH, total)}/{total}", flush=True)
        out_path.write_text(
            json.dumps(data, ensure_ascii=False, indent=4) + "\n",
            encoding="utf-8",
        )
        time.sleep(SLEEP)

    for key, value in en.items():
        data.setdefault(key, value)

    locale_names = {
        "en": "English",
        "fr": "Français",
        "es": "Español",
        "ar": "العربية",
        "id": "Bahasa Indonesia",
        "hi": "हिन्दी",
    }
    for loc, name in locale_names.items():
        data[f"locale.name.{loc}"] = name

    sorted_data = dict(sorted(data.items(), key=lambda x: x[0]))
    out_path.write_text(
        json.dumps(sorted_data, ensure_ascii=False, indent=4) + "\n",
        encoding="utf-8",
    )
    print(f"Wrote {out_path} ({len(sorted_data)} keys)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
