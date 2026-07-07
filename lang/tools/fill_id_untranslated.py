#!/usr/bin/env python3
"""Re-translate id.json keys that still match en.json."""

from __future__ import annotations

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
ID_PATH = BASE / "id.json"
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
    en: dict[str, str] = json.loads(EN_PATH.read_text(encoding="utf-8"))
    id_data: dict[str, str] = json.loads(ID_PATH.read_text(encoding="utf-8"))
    translator = GoogleTranslator(source="en", target="id")

    keys = [
        k
        for k in sorted(en.keys())
        if id_data.get(k) == en[k]
        and not k.startswith("locale.name.")
        and en[k].strip()
    ]
    print(f"Retranslating {len(keys)} keys...")
    for start in range(0, len(keys), BATCH):
        for key in keys[start : start + BATCH]:
            translated = translate(en[key], translator)
            if translated and translated != en[key]:
                id_data[key] = translated
        print(f"  {min(start + BATCH, len(keys))}/{len(keys)}", flush=True)
        ID_PATH.write_text(
            json.dumps(dict(sorted(id_data.items())), ensure_ascii=False, indent=4) + "\n",
            encoding="utf-8",
        )
        time.sleep(SLEEP)
    print("Done")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
