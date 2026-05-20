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
    | "${SSH_CMD[@]}" "$SSH_TARGET" "sudo -n /usr/bin/mkdir -p '${remote_dir}' && sudo -n /usr/bin/tar -xzf - -C '${remote_dir}' && sudo -n /usr/bin/chown 33:33 '${remote_path}'"
}

REMOTE_SEED="${NAS_PROJECT}/wp-content/seed-content.php"

AUTO=0
for arg in "$@"; do
    if [[ "$arg" == "--yes" ]]; then
        AUTO=1
    fi
done
if [[ "${CI:-}" == "true" ]] || [[ "$AUTO" == "1" ]]; then
    :
else
    cat <<'WARNING'
Staging content seed updates WordPress pages, menu, and theme mods.

Before continuing:
1. WordPress must already be installed on staging (first-time wizard done).
2. Board Games theme must be installed and active (upload zip in admin if needed).
WARNING

    read -r -p "Type SEED to continue: " CONFIRM
    if [[ "$CONFIRM" != "SEED" ]]; then
        echo "Cancelled."
        exit 1
    fi
fi

upload_file "$ROOT_DIR/scripts/seed-content.php" "$REMOTE_SEED"

"${SSH_CMD[@]}" "$SSH_TARGET" \
  "cd '${NAS_PROJECT}' && OUTPUT=\$(${NAS_DOCKER} compose exec -T wordpress php /var/www/html/wp-content/seed-content.php --wp-path=/var/www/html --site-url='${STAGING_URL}' --public=0 2>&1); printf '%s\n' \"\$OUTPUT\"; case \"\$OUTPUT\" in *'Seeded WordPress content'*) sudo -n /usr/bin/rm -f '${REMOTE_SEED}' ;; *) echo 'Seed did not complete.' >&2; exit 1 ;; esac"

echo "Staging seed finished. Review: ${STAGING_URL}"
