#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
: "${LOCAL_WP_PATH:=/Users/ola/Local Sites/slingan/app/public}"

SRC="${ROOT_DIR}/wordpress/wp-content/mu-plugins"
DEST="${LOCAL_WP_PATH}/wp-content/mu-plugins"

mkdir -p "$DEST"
rsync -az "$SRC/" "$DEST/" --delete --exclude='.gitkeep'

echo "Synced mu-plugins to Local WP: ${DEST}"
