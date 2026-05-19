#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ -f "$ROOT_DIR/.env.production" ]]; then
  set -a
  # shellcheck source=/dev/null
  source "$ROOT_DIR/.env.production"
  set +a
fi

: "${PRODUCTION_HOST:?Set PRODUCTION_HOST}"
: "${PRODUCTION_USER:?Set PRODUCTION_USER}"
: "${PRODUCTION_WP_CONTENT:?Set PRODUCTION_WP_CONTENT to the production wp-content path}"
: "${PRODUCTION_URL:=}"

SSH_TARGET="${PRODUCTION_USER}@${PRODUCTION_HOST}"
SSH_CMD=(ssh)
RSYNC_OPTS=(-az)
if [[ -n "${SSH_KEY:-}" ]]; then
  SSH_CMD=(ssh -i "$SSH_KEY")
  RSYNC_OPTS=(-az -e "ssh -i $SSH_KEY")
fi

cat <<'WARNING'
Production deploy is theme-code only.

Before continuing:
1. Confirm the production WordPress wp-content path.
2. Take a production database and uploads backup.
WARNING

read -r -p "Type DEPLOY to continue: " CONFIRM
if [[ "$CONFIRM" != "DEPLOY" ]]; then
  echo "Cancelled."
  exit 1
fi

"${SSH_CMD[@]}" "$SSH_TARGET" "mkdir -p '${PRODUCTION_WP_CONTENT}/themes'"
rsync "${RSYNC_OPTS[@]}" --delete "$ROOT_DIR/wordpress/wp-content/themes/slingan/" "$SSH_TARGET:$PRODUCTION_WP_CONTENT/themes/slingan/"

echo "Production theme deployed."

if [[ -n "$PRODUCTION_URL" ]]; then
  echo "Checking production URL: ${PRODUCTION_URL}"
  curl -fsSIL "$PRODUCTION_URL" >/dev/null
  echo "Production is responding."
fi
