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
: "${STAGING_URL:=http://${NAS_HOST}:8083}"
: "${NAS_DOCKER:=sudo -n /usr/local/bin/docker}"

if [[ -z "${SSH_KEY:-}" && -f "$HOME/.ssh/id_ed25519_nas_bot" ]]; then
  SSH_KEY="$HOME/.ssh/id_ed25519_nas_bot"
fi

SSH_TARGET="${NAS_USER}@${NAS_HOST}"
SSH_CMD=(ssh -p "$NAS_PORT")
if [[ -n "${SSH_KEY:-}" ]]; then
  SSH_CMD=(ssh -i "$SSH_KEY" -p "$NAS_PORT")
fi

upload_file() {
  local source_file="$1"
  local remote_path="$2"
  local source_dir source_name remote_dir

  source_dir="$(dirname "$source_file")"
  source_name="$(basename "$source_file")"
  remote_dir="$(dirname "$remote_path")"

  COPYFILE_DISABLE=1 tar --no-xattrs -C "$source_dir" -czf - "$source_name" \
    | "${SSH_CMD[@]}" "$SSH_TARGET" "sudo -n /usr/bin/mkdir -p '${remote_dir}' && sudo -n /usr/bin/tar -xzf - -C '${remote_dir}'"
}

upload_directory_contents() {
  local source_dir="$1"
  local remote_dir="$2"

  COPYFILE_DISABLE=1 tar --no-xattrs -C "$source_dir" -czf - . \
    | "${SSH_CMD[@]}" "$SSH_TARGET" "sudo -n /usr/bin/rm -rf '${remote_dir}' && sudo -n /usr/bin/mkdir -p '${remote_dir}' && sudo -n /usr/bin/tar -xzf - -C '${remote_dir}' && sudo -n /usr/bin/chown -R 33:33 '${remote_dir}'"
}

echo "Deploying staging to ${SSH_TARGET}:${NAS_PROJECT}"

"${SSH_CMD[@]}" "$SSH_TARGET" "mkdir -p '${NAS_PROJECT}/wp-content/themes' '${NAS_PROJECT}/backups'"

upload_file "$ROOT_DIR/docker/staging/docker-compose.yml" "$NAS_PROJECT/docker-compose.yml"
upload_directory_contents "$ROOT_DIR/wordpress/wp-content/themes/slingan" "$NAS_PROJECT/wp-content/themes/slingan"

"${SSH_CMD[@]}" "$SSH_TARGET" "cd '${NAS_PROJECT}' && test -f .env && ${NAS_DOCKER} compose up -d"

echo "Waiting for staging health check: ${STAGING_URL}"
for _ in {1..30}; do
  if curl -fsSIL "$STAGING_URL" >/dev/null; then
    echo "Staging is responding: ${STAGING_URL}"
    exit 0
  fi
  sleep 5
done

echo "Staging did not respond within the expected time." >&2
exit 1
