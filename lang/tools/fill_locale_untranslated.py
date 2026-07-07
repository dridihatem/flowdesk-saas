#!/usr/bin/env python3
"""Re-translate keys in lang/{locale}.json that still match en.json."""

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
    print("Use .venv-translate/bin/python", file=sys.stderr)
    sys.exit(1)

BASE = Path(__file__).resolve().parent.parent
EN_PATH = BASE / "en.json"
BATCH = 30
SLEEP = 0.4

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


def translate(text: str, translator: GoogleTranslator) -> str:
    protected, tokens = protect(text)
    try:
        out = translator.translate(protected)
    except Exception:
        return text
    return restore(out, tokens)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("locale", help="Locale code, e.g. hi")
    parser.add_argument("google_target", nargs="?", help="Google target code")
    args = parser.parse_args()
    google_target = args.google_target or args.locale
    loc_path = BASE / f"{args.locale}.json"

    en: dict[str, str] = json.loads(EN_PATH.read_text(encoding="utf-8"))
    loc_data: dict[str, str] = json.loads(loc_path.read_text(encoding="utf-8"))
    translator = GoogleTranslator(source="en", target=google_target)

    keys = [
        k
        for k in sorted(en.keys())
        if loc_data.get(k) == en[k]
        and not k.startswith("locale.name.")
        and en[k].strip()
    ]
    print(f"Retranslating {len(keys)} keys for {args.locale}...")
    for start in range(0, len(keys), BATCH):
        for key in keys[start : start + BATCH]:
            translated = translate(en[key], translator)
            if translated and translated != en[key]:
                loc_data[key] = translated
        print(f"  {min(start + BATCH, len(keys))}/{len(keys)}", flush=True)
        loc_path.write_text(
            json.dumps(dict(sorted(loc_data.items())), ensure_ascii=False, indent=4) + "\n",
            encoding="utf-8",
        )
        time.sleep(SLEEP)
    print("Done")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
