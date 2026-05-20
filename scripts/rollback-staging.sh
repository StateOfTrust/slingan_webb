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
: "${BACKUP_ID:?Set BACKUP_ID to a folder under ${NAS_PROJECT}/backups}"
: "${NAS_DOCKER:=sudo -n /usr/local/bin/docker}"

if [[ -z "${SSH_KEY:-}" && -f "$HOME/.ssh/id_ed25519_nas_bot" ]]; then
  SSH_KEY="$HOME/.ssh/id_ed25519_nas_bot"
fi

SSH_TARGET="${NAS_USER}@${NAS_HOST}"
REMOTE_BACKUP_DIR="${NAS_PROJECT}/backups/${BACKUP_ID}"
SSH_CMD=(ssh -p "$NAS_PORT")
if [[ -n "${SSH_KEY:-}" ]]; then
  SSH_CMD=(ssh -i "$SSH_KEY" -p "$NAS_PORT")
fi

cat <<WARNING
This will replace staging wp-content and database from backup:

  ${REMOTE_BACKUP_DIR}

Type the backup folder name again to confirm.
WARNING

read -r -p "BACKUP_ID (${BACKUP_ID}): " CONFIRM
if [[ "$CONFIRM" != "$BACKUP_ID" ]]; then
  echo "Cancelled."
  exit 1
fi

"${SSH_CMD[@]}" "$SSH_TARGET" "cd '${NAS_PROJECT}' && test -f '${REMOTE_BACKUP_DIR}/database.sql.gz' && test -f '${REMOTE_BACKUP_DIR}/wp-content.tar.gz' && sudo -n /usr/bin/rm -rf wp-content && sudo -n /usr/bin/tar -xzf '${REMOTE_BACKUP_DIR}/wp-content.tar.gz' && sudo -n /usr/bin/chown -R 33:33 wp-content && gzip -dc '${REMOTE_BACKUP_DIR}/database.sql.gz' | ${NAS_DOCKER} compose exec -T db sh -c 'mariadb -uroot -p\"\$MYSQL_ROOT_PASSWORD\" wordpress' && ${NAS_DOCKER} compose restart wordpress"

echo "Rolled staging back to backup: ${BACKUP_ID}"
