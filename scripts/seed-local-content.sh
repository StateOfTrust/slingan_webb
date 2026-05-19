#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

: "${LOCAL_WP_PATH:=/Users/ola/Local Sites/slingan/app/public}"
: "${LOCAL_SITE_URL:=http://slingan.local}"
: "${LOCAL_DB_SOCKET:=}"

# shellcheck source=_find-socket.sh
source "$ROOT_DIR/scripts/_find-socket.sh"
if [[ -z "$LOCAL_DB_SOCKET" ]]; then
  LOCAL_DB_SOCKET="$(resolve_mysql_socket "$LOCAL_WP_PATH" "$LOCAL_SITE_URL")"
fi

if [[ -z "$LOCAL_DB_SOCKET" || ! -S "$LOCAL_DB_SOCKET" ]]; then
  echo "Could not resolve Local MySQL socket for:" >&2
  echo "  WP path: ${LOCAL_WP_PATH}" >&2
  echo "  Site URL: ${LOCAL_SITE_URL}" >&2
  echo "Is the Slingan site running in Local?" >&2
  exit 1
fi

PHP_ARGS=(
  -d "mysqli.default_socket=$LOCAL_DB_SOCKET"
  -d "pdo_mysql.default_socket=$LOCAL_DB_SOCKET"
  -d "mysql.default_socket=$LOCAL_DB_SOCKET"
)

OUTPUT="$(php "${PHP_ARGS[@]}" "$ROOT_DIR/scripts/seed-content.php" \
  --wp-path="$LOCAL_WP_PATH" \
  --site-url="$LOCAL_SITE_URL" \
  --public=0 2>&1)"

printf '%s\n' "$OUTPUT"

if [[ "$OUTPUT" != *"Seeded WordPress"* ]]; then
  echo "Seed did not complete. Check that Local WP is running." >&2
  exit 1
fi
