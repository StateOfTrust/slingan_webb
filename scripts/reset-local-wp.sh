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

PHP_ARGS=()
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

if [[ ! -f "$LOCAL_WP_PATH/wp-config.php" ]]; then
  echo "Local WordPress not found at ${LOCAL_WP_PATH}" >&2
  echo "Create a Local site named 'slingan' first, then run this again." >&2
  exit 1
fi

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
