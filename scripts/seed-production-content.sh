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
: "${LOOPIA_WP_PATH:?Set LOOPIA_WP_PATH to the production WordPress root}"
: "${PRODUCTION_URL:?Set PRODUCTION_URL, for example https://slingan.se}"

SSH_TARGET="${LOOPIA_USER}@${LOOPIA_HOST}"
SSH_CMD=(ssh)
RSYNC_OPTS=(-az)
if [[ -n "${SSH_KEY:-}" ]]; then
  SSH_CMD=(ssh -i "$SSH_KEY")
  RSYNC_OPTS=(-az -e "ssh -i $SSH_KEY")
fi

REMOTE_SEED="${LOOPIA_WP_PATH}/wp-content/slingan_webb-seed-content.php"

cat <<'WARNING'
Production content seed updates WordPress-owned pages and options only.

Before continuing:
1. Take a production database backup.
2. Board Games must be installed and activatable on production.
3. Confirm PRODUCTION_URL is correct.
WARNING

read -r -p "Type SEED to continue: " CONFIRM
if [[ "$CONFIRM" != "SEED" ]]; then
  echo "Cancelled."
  exit 1
fi

rsync "${RSYNC_OPTS[@]}" "$ROOT_DIR/scripts/seed-content.php" "$SSH_TARGET:$REMOTE_SEED"

"${SSH_CMD[@]}" "$SSH_TARGET" \
  "OUTPUT=\$(php '${REMOTE_SEED}' --wp-path='${LOOPIA_WP_PATH}' --site-url='${PRODUCTION_URL}' --public=1 2>&1); printf '%s\n' \"\$OUTPUT\"; case \"\$OUTPUT\" in *'Seeded WordPress'*) rm -f '${REMOTE_SEED}' ;; *) echo 'Seed did not complete.' >&2; exit 1 ;; esac"

echo "Production seed finished."
