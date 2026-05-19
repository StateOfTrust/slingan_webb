#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

: "${LOCAL_WP_PATH:=/Users/ola/Local Sites/slingan/app/public}"
: "${LOCAL_SITE_URL:=http://slingan.local}"
: "${LOCAL_DB_SOCKET:=}"

PHP_ARGS=()
if [[ -n "$LOCAL_DB_SOCKET" && -S "$LOCAL_DB_SOCKET" ]]; then
  PHP_ARGS=(
    -d "mysqli.default_socket=$LOCAL_DB_SOCKET"
    -d "pdo_mysql.default_socket=$LOCAL_DB_SOCKET"
    -d "mysql.default_socket=$LOCAL_DB_SOCKET"
  )
elif [[ -z "$LOCAL_DB_SOCKET" ]]; then
  for candidate in /Users/ola/Library/Application\ Support/Local/run/*/mysql/mysqld.sock; do
    if [[ -S "$candidate" ]]; then
      PHP_ARGS=(
        -d "mysqli.default_socket=$candidate"
        -d "pdo_mysql.default_socket=$candidate"
        -d "mysql.default_socket=$candidate"
      )
      break
    fi
  done
fi

OUTPUT="$(php "${PHP_ARGS[@]}" "$ROOT_DIR/scripts/seed-content.php" \
  --wp-path="$LOCAL_WP_PATH" \
  --site-url="$LOCAL_SITE_URL" \
  --public=0 2>&1)"

printf '%s\n' "$OUTPUT"

if [[ "$OUTPUT" != *"Seeded WordPress"* ]]; then
  echo "Seed did not complete. Check that Local WP is running." >&2
  exit 1
fi
