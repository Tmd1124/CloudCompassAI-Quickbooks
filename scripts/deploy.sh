#!/usr/bin/env bash
# Usage: scripts/deploy.sh [--dry-run]   Uploads to the quickbooks.cloudcompassai.com FTP docroot only.
set -euo pipefail
cd "$(dirname "$0")/.."
set -a; . ./.env.deploy; set +a
DRY=0; [ "${1:-}" = "--dry-run" ] && DRY=1
BASE="ftp://ftp.hostgator.com:${FTP_PORT}"
up() {
  echo "$1 -> /$2"
  [ $DRY = 1 ] && return
  curl -sS --fail --ssl-reqd --disable-epsv --ftp-create-dirs --connect-to "ftp.hostgator.com:${FTP_PORT}:${FTP_HOST}:${FTP_PORT}" \
    --user "${FTP_USER}:${FTP_PASS}" -T "$1" "$BASE/$2"
}
while IFS= read -r f; do up "$f" "${f#public/}"; done < <(find public -type f | sort)
for f in src/*.php src/.htaccess; do up "$f" "$f"; done
up config/.htaccess config/.htaccess
up config/config.server.php config/config.php
up storage/.htaccess storage/.htaccess
echo "done (dry-run=$DRY)"
