#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ -f "$ROOT_DIR/.env.production" ]]; then
  set -a
  # shellcheck source=/dev/null
  source "$ROOT_DIR/.env.production"
  set +a
fi

: "${LOOPIA_HOST:?Set LOOPIA_HOST}"
: "${LOOPIA_USER:?Set LOOPIA_USER}"
: "${LOOPIA_WP_CONTENT:?Set LOOPIA_WP_CONTENT to the production wp-content path}"
: "${PRODUCTION_URL:=}"

SSH_TARGET="${LOOPIA_USER}@${LOOPIA_HOST}"
SSH_CMD=(ssh)
RSYNC_OPTS=(-az)
if [[ -n "${SSH_KEY:-}" ]]; then
  SSH_CMD=(ssh -i "$SSH_KEY")
  RSYNC_OPTS=(-az -e "ssh -i $SSH_KEY")
fi

cat <<'WARNING'
Production deploy syncs Git-tracked wp-content (MU plugins + fallback slingan theme).

Before continuing:
1. Confirm the Loopia wp-content path (same account as Mörk Quest).
2. Board Games theme must already be installed on production (not in Git).
3. Take a production database and uploads backup.
WARNING

read -r -p "Type DEPLOY to continue: " CONFIRM
if [[ "$CONFIRM" != "DEPLOY" ]]; then
  echo "Cancelled."
  exit 1
fi

"${SSH_CMD[@]}" "$SSH_TARGET" "mkdir -p '${LOOPIA_WP_CONTENT}/themes' '${LOOPIA_WP_CONTENT}/mu-plugins'"
rsync "${RSYNC_OPTS[@]}" --delete \
  "$ROOT_DIR/wordpress/wp-content/mu-plugins/" \
  "$SSH_TARGET:$LOOPIA_WP_CONTENT/mu-plugins/"
rsync "${RSYNC_OPTS[@]}" --delete \
  "$ROOT_DIR/wordpress/wp-content/themes/slingan/" \
  "$SSH_TARGET:$LOOPIA_WP_CONTENT/themes/slingan/"

echo "Production wp-content deployed."

if [[ -n "$PRODUCTION_URL" ]]; then
  echo "Checking production URL: ${PRODUCTION_URL}"
  curl -fsSIL "$PRODUCTION_URL" >/dev/null
  echo "Production is responding."
fi
