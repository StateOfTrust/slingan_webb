#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ -f "$ROOT_DIR/.env.staging" ]]; then
  set -a
  # shellcheck source=/dev/null
  source "$ROOT_DIR/.env.staging"
  set +a
fi

: "${NAS_HOST:=100.72.42.84}"
: "${NAS_PORT:=9250}"
: "${NAS_USER:=bot}"
: "${NAS_PROJECT:=/volume1/docker/slingan-staging}"
: "${BACKUP_NAME:=slingan-staging-$(date +%Y%m%d-%H%M%S)}"
: "${NAS_DOCKER:=sudo -n /usr/local/bin/docker}"

if [[ -z "${SSH_KEY:-}" && -f "$HOME/.ssh/id_ed25519_nas_bot" ]]; then
  SSH_KEY="$HOME/.ssh/id_ed25519_nas_bot"
fi

SSH_TARGET="${NAS_USER}@${NAS_HOST}"
REMOTE_BACKUP_DIR="${NAS_PROJECT}/backups/${BACKUP_NAME}"
SSH_CMD=(ssh -p "$NAS_PORT")
if [[ -n "${SSH_KEY:-}" ]]; then
  SSH_CMD=(ssh -i "$SSH_KEY" -p "$NAS_PORT")
fi

"${SSH_CMD[@]}" "$SSH_TARGET" "mkdir -p '${REMOTE_BACKUP_DIR}' && cd '${NAS_PROJECT}' && ${NAS_DOCKER} compose exec -T db sh -c 'mariadb-dump -uroot -p\"\$MYSQL_ROOT_PASSWORD\" wordpress' | gzip > '${REMOTE_BACKUP_DIR}/database.sql.gz' && tar -czf '${REMOTE_BACKUP_DIR}/wp-content.tar.gz' wp-content"

echo "Staging backup created: ${REMOTE_BACKUP_DIR}"
