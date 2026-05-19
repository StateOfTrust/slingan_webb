#!/usr/bin/env bash
# Resolve Local-by-Flywheel MySQL socket for a WordPress install.
#
# 1) Prefer matching LOCAL_WP_PATH to ~/Library/Application Support/Local/sites.json
#    (avoids wrong DB when several sites share DB name "local").
# 2) Fallback: socket whose wp_options.siteurl equals LOCAL_SITE_URL.

find_local_socket_by_url() {
  local target_url="$1"
  local match=""
  for sock in "${HOME}/Library/Application Support/Local/run/"*/mysql/mysqld.sock; do
    [[ -S "$sock" ]] || continue
    local found
    found="$(php -d "mysqli.default_socket=$sock" -r '
      $m = @new mysqli("localhost", "root", "root", "local");
      if ($m->connect_errno) exit;
      $r = @$m->query("SELECT option_value FROM wp_options WHERE option_name = \"siteurl\" LIMIT 1");
      if ($r && ($row = $r->fetch_row())) echo $row[0];
    ' 2>/dev/null)"
    if [[ "$found" == "$target_url" ]]; then
      match="$sock"
      break
    fi
  done
  printf '%s' "$match"
}

find_local_socket_by_wp_path() {
  local wp_public="$1"
  local sites_json="${HOME}/Library/Application Support/Local/sites.json"
  [[ -f "$sites_json" ]] || return 1
  [[ -d "$wp_public" ]] || return 1

  local site_dir
  site_dir="$(cd "$(dirname "$wp_public")/.." && pwd)" || return 1

  local sock
  sock="$(SOT_RESOLVE_SITE_DIR="$site_dir" python3 - <<'PY' 2>/dev/null || true
import json, os, sys
site_dir = os.path.realpath(os.environ["SOT_RESOLVE_SITE_DIR"])
path = os.path.expanduser("~/Library/Application Support/Local/sites.json")
with open(path) as f:
    sites = json.load(f)
for sid, meta in sites.items():
    raw = meta.get("path") or ""
    p = os.path.realpath(os.path.expanduser(raw.replace("~", str(os.path.expanduser("~")))))
    if site_dir == p or site_dir.startswith(p + os.sep):
        sock = os.path.expanduser(f"~/Library/Application Support/Local/run/{sid}/mysql/mysqld.sock")
        print(sock)
        sys.exit(0)
sys.exit(1)
PY
)"
  [[ -n "$sock" && -S "$sock" ]] || return 1
  printf '%s' "$sock"
}

resolve_mysql_socket() {
  local wp_public="${1:-}"
  local site_url="${2:-}"
  local sock=""

  if [[ -n "$wp_public" ]]; then
    sock="$(find_local_socket_by_wp_path "$wp_public" 2>/dev/null || true)"
  fi
  if [[ -z "$sock" || ! -S "$sock" ]]; then
    sock="$(find_local_socket_by_url "$site_url")"
  fi
  printf '%s' "$sock"
}
