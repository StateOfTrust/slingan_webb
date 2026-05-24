#!/usr/bin/env bash
# Build slingan-X.Y.Z.zip for WordPress admin upload or manual unzip on the server.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME_DIR="$ROOT_DIR/wordpress/wp-content/themes/slingan"
DIST_DIR="$ROOT_DIR/dist"

if [[ ! -f "$THEME_DIR/style.css" ]]; then
  echo "Theme not found: $THEME_DIR" >&2
  exit 1
fi

VERSION="$(
  sed -n 's/^Version:[[:space:]]*//p' "$THEME_DIR/style.css" | head -n1 | tr -d '\r'
)"
if [[ -z "$VERSION" ]]; then
  echo "Could not read Version from style.css" >&2
  exit 1
fi

ZIP_NAME="slingan-${VERSION}.zip"
OUT="$DIST_DIR/$ZIP_NAME"

mkdir -p "$DIST_DIR"
rm -f "$OUT"

# Zip must contain a top-level "slingan/" folder (WordPress upload requirement).
COPYFILE_DISABLE=1 tar -C "$(dirname "$THEME_DIR")" \
  --exclude='.DS_Store' \
  -czf - slingan \
  | (cd "$DIST_DIR" && tar -xzf - && zip -rq "$ZIP_NAME" slingan && rm -rf slingan)

echo "Created: $OUT"
echo ""
echo "Install on production:"
echo "  1. wp-admin → Utseende → Teman → Lägg till → Ladda upp tema → choose $ZIP_NAME"
echo "  2. Or unzip to wp-content/themes/slingan/ on the server"
echo ""
echo "This repo’s usual path: ./scripts/deploy-production.sh (rsync, no zip)"
