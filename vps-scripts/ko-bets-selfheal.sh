#!/bin/bash
# ko-bets self-heal: re-deploy agents into the Hermes container if missing.
# Runs from host cron. Host FS persists across container redeploys.
C=hermes-agent-c7dm-hermes-agent-1
S=/home/hermes/sites/ko-bets
LOG=/var/log/ko-bets-selfheal.log
docker ps --format '{{.Names}}' | grep -q "^$C$" || exit 0
if docker exec "$C" test -f "$S/lib_wp.py" \
   && docker exec "$C" test -f "$S/.env" \
   && docker exec "$C" bash -lc 'crontab -l 2>/dev/null | grep -q ko-bets/run.sh'; then
  exit 0   # healthy, nothing to do
fi
echo "[$(date -u +%FT%TZ)] container missing ko-bets agents -> redeploying" >> "$LOG"
bash /root/ko-bets-deploy.sh >> "$LOG" 2>&1
echo "[$(date -u +%FT%TZ)] redeploy finished" >> "$LOG"
