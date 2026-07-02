#!/usr/bin/env bash

# Convert PNG/JPG/JPEG files under a target directory (default: public/images)
# to WebP variants alongside the originals.
#
# Usage: ./scripts/convert-images.sh [dir] [--force]
#
# Requirements: cwebp (Homebrew: `brew install webp`).
# Re-running is safe: existing variants are skipped unless --force is passed.

set -euo pipefail

FORCE=0
TARGET="public/images"
for arg in "$@"; do
    case "$arg" in
        -f|--force) FORCE=1 ;;
        *) TARGET="$arg" ;;
    esac
done

cd "$(dirname "$0")/.."

if ! command -v cwebp >/dev/null 2>&1; then
    echo "cwebp not found. Install with: brew install webp" >&2
    exit 1
fi

if [[ ! -d "$TARGET" ]]; then
    echo "target directory not found: $TARGET" >&2
    exit 1
fi

WEBP_QUALITY=85
MIN_SAVINGS_PERCENT=10

file_size() {
    stat -f%z "$1" 2>/dev/null || stat -c%s "$1"
}

keep_if_smaller() {
    local src="$1"
    local out="$2"

    local src_size out_size savings
    src_size=$(file_size "$src")
    out_size=$(file_size "$out")

    if (( src_size <= 0 )); then
        return
    fi

    savings=$(( (src_size - out_size) * 100 / src_size ))
    if (( savings < MIN_SAVINGS_PERCENT )); then
        rm "$out"
        echo "drop  $out (only ${savings}% smaller)"
    else
        echo "webp  $out (${savings}% smaller)"
    fi
}

convert_file() {
    local src="$1"
    local webp="${src%.*}.webp"

    if [[ $FORCE -eq 0 && -f "$webp" && "$webp" -nt "$src" ]]; then
        echo "skip  $webp"
    else
        cwebp -q "$WEBP_QUALITY" -quiet "$src" -o "$webp"
        keep_if_smaller "$src" "$webp"
    fi
}

while IFS= read -r -d '' img; do
    convert_file "$img"
done < <(
    find "$TARGET" \
        \( -iname '*.png' -o -iname '*.jpg' -o -iname '*.jpeg' \) \
        -type f -print0
)

echo "done."
