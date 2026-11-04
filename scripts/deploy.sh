#!/usr/bin/env bash
# Usage: scripts/deploy.sh [--dry-run]   Uploads to the quickbooks.cloudcompassai.com FTP docroot only.
# HostGator's FTP often answers 451 after a successful transfer, so success is judged by a directory listing.
set -uo pipefail
cd "$(dirname "$0")/.."
set -a; . ./.env.deploy; set +a
DRY=0; [ "${1:-}" = "--dry-run" ] && DRY=1
BASE="ftp://ftp.hostgator.com:${FTP_PORT}"
C=(curl -sS --ssl-reqd --disable-epsv --connect-timeout 15 --max-time 60 --connect-to "ftp.hostgator.com:${FTP_PORT}:${FTP_HOST}:${FTP_PORT}" --user "${FTP_USER}:${FTP_PASS}")
PAIRS=()
while IFS= read -r f; do PAIRS+=("$f|${f#public/}"); done < <(find public -type f | sort)
for f in src/*.php src/.htaccess; do PAIRS+=("$f|$f"); done
PAIRS+=("config/.htaccess|config/.htaccess" "config/config.server.php|config/config.php" "storage/.htaccess|storage/.htaccess" "scripts/sync.php|cron/sync.php" "scripts/.htaccess|cron/.htaccess")
for p in "${PAIRS[@]}"; do
  echo "${p%%|*} -> /${p##*|}"
  [ $DRY = 1 ] || "${C[@]}" --ftp-create-dirs -T "${p%%|*}" "$BASE/${p##*|}" >/dev/null 2>&1
done
FAIL=0
if [ $DRY = 0 ]; then
  declare -A LS
  for p in "${PAIRS[@]}"; do
    r="${p##*|}"; d=$(dirname "$r"); [ "$d" = "." ] && d=""
    k="${d:-_root}"; [ -n "${LS[$k]+x}" ] || LS[$k]=$("${C[@]}" --list-only "$BASE/${d:+$d/}" 2>/dev/null)
    grep -qxF "$(basename "$r")" <<<"${LS[$k]}" || { echo "MISSING: /$r"; FAIL=1; }
  done
fi
echo "done (dry-run=$DRY, missing=$FAIL)"
exit $FAIL
