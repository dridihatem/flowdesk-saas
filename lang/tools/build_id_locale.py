#!/usr/bin/env python3
"""Build lang/id.json from en.json via Google Translate (Indonesian)."""

from __future__ import annotations

import json
import re
import sys
import time
from pathlib import Path

try:
    from deep_translator import GoogleTranslator
except ImportError:
    print("Install: pip install deep-translator", file=sys.stderr)
    sys.exit(1)

BASE = Path(__file__).resolve().parent.parent
EN_PATH = BASE / "en.json"
ID_PATH = BASE / "id.json"
BATCH = 40
SLEEP = 0.35

PLACEHOLDER_RE = re.compile(
    r"(:[a-zA-Z_][a-zA-Z0-9_]*)|(\{\{[^}]+\}\})|(\:[a-zA-Z_][a-zA-Z0-9_]*\})"
)


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
    en: dict[str, str] = json.loads(EN_PATH.read_text(encoding="utf-8"))
    existing: dict[str, str] = {}
    if ID_PATH.exists():
        existing = json.loads(ID_PATH.read_text(encoding="utf-8"))

    translator = GoogleTranslator(source="en", target="id")
    keys = sorted(en.keys())
    total = len(keys)
    id_data: dict[str, str] = dict(existing)

    for start in range(0, total, BATCH):
        batch = keys[start : start + BATCH]
        for key in batch:
            if key in id_data and id_data[key] != en[key]:
                continue
            src = en[key]
            if src == key:
                id_data[key] = key
                continue
            id_data[key] = translate_value(src, translator)
        done = min(start + BATCH, total)
        print(f"Translated {done}/{total}", flush=True)
        ID_PATH.write_text(
            json.dumps(id_data, ensure_ascii=False, indent=4) + "\n",
            encoding="utf-8",
        )
        time.sleep(SLEEP)

    # Ensure every en key exists
    for key, value in en.items():
        id_data.setdefault(key, value)

    ksort = dict(sorted(id_data.items(), key=lambda x: x[0]))
    ID_PATH.write_text(
        json.dumps(ksort, ensure_ascii=False, indent=4) + "\n",
        encoding="utf-8",
    )
    print(f"Wrote {ID_PATH} ({len(ksort)} keys)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
