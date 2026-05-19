#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
: "${LOCAL_WP_PATH:=/Users/ola/Local Sites/slingan/app/public}"

TARGET="${LOCAL_WP_PATH}/wp-content/themes/slingan"

mkdir -p "$TARGET"
rsync -az --delete "$ROOT_DIR/wordpress/wp-content/themes/slingan/" "$TARGET/"

echo "Synced theme to Local WP: ${TARGET}"
