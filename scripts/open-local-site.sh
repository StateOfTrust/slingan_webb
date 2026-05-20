#!/usr/bin/env bash
set -euo pipefail

: "${LOCAL_SITE_URL:=http://slingan.local}"

if [[ -d "/Applications/Google Chrome.app" ]]; then
  open -a "Google Chrome" "$LOCAL_SITE_URL"
else
  open "$LOCAL_SITE_URL"
fi

echo "Opened $LOCAL_SITE_URL"
