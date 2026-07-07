#!/usr/bin/env bash
# Build installable .zip packages for all Qatar module stubs.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
OUT="$ROOT/zips"
mkdir -p "$OUT"

for dir in "$ROOT"/qatar-*/; do
    slug="$(basename "$dir")"
    if [[ ! -f "$dir/module.json" ]]; then
        continue
    fi
    zip_path="$OUT/${slug}.zip"
    rm -f "$zip_path"
    (cd "$dir" && zip -qr "$zip_path" . -x '*.DS_Store')
    echo "Built $zip_path"
done

echo "Done. Install via Settings → Modules → Upload zip."
