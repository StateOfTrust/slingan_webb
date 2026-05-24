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

# Clear page caches so theme/JS changes show immediately (WP Fastest Cache + LiteSpeed).
"${SSH_CMD[@]}" "$SSH_TARGET" "cd '${LOOPIA_WP_PATH}' && wp cache flush >/dev/null 2>&1; wp eval '
if (class_exists(\"WpFastestCache\")) {
    \$c = new WpFastestCache();
    if (method_exists(\$c, \"deleteCache\")) {
        \$c->deleteCache(true);
    }
}
if (function_exists(\"litespeed_purge_all\")) {
    litespeed_purge_all();
}
' >/dev/null 2>&1" || true
echo "Production page cache purged (if WP-CLI available)."

if [[ -n "$PRODUCTION_URL" ]]; then
  echo "Checking production URL: ${PRODUCTION_URL}"
  curl -fsSIL "$PRODUCTION_URL" >/dev/null
  echo "Production is responding."
fi
