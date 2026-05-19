#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

: "${LOCAL_WP_PATH:=/Users/ola/Local Sites/slingan/app/public}"
: "${LOCAL_SITE_URL:=http://slingan.local}"
: "${LOCAL_ADMIN_USER:=ola}"
: "${LOCAL_ADMIN_PASSWORD:=othello}"
: "${LOCAL_ADMIN_EMAIL:=ola@slingan.local}"

if [[ "${1:-}" != "--yes" ]]; then
  echo "This will DELETE the entire local WordPress database and reinstall from scratch."
  echo "Site path: ${LOCAL_WP_PATH}"
  echo "Re-run with --yes to continue."
  exit 1
fi

if [[ ! -f "$LOCAL_WP_PATH/wp-config.php" ]]; then
  echo "Local WordPress not found at ${LOCAL_WP_PATH}" >&2
  echo "Create a Local site named 'slingan' first, then run this again." >&2
  exit 1
fi

# shellcheck source=_find-socket.sh
source "$ROOT_DIR/scripts/_find-socket.sh"
: "${LOCAL_DB_SOCKET:=}"
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
echo "Using MySQL socket: ${LOCAL_DB_SOCKET}"

PHP_ARGS=(
  -d "mysqli.default_socket=$LOCAL_DB_SOCKET"
  -d "pdo_mysql.default_socket=$LOCAL_DB_SOCKET"
  -d "mysql.default_socket=$LOCAL_DB_SOCKET"
)

echo "Reinstalling WordPress..."
php "${PHP_ARGS[@]}" "$ROOT_DIR/scripts/reset-local-wp.php" \
  --wp-path="$LOCAL_WP_PATH" \
  --site-url="$LOCAL_SITE_URL" \
  --admin-user="$LOCAL_ADMIN_USER" \
  --admin-password="$LOCAL_ADMIN_PASSWORD" \
  --admin-email="$LOCAL_ADMIN_EMAIL"

"$ROOT_DIR/scripts/sync-local-theme.sh"
"$ROOT_DIR/scripts/seed-local-content.sh"

echo "Fresh Slingan install ready at ${LOCAL_SITE_URL}"
echo "Admin: ${LOCAL_ADMIN_USER} / ${LOCAL_ADMIN_PASSWORD}"
