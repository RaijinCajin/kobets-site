#!/bin/bash
set -a
[ -f /home/hermes/sites/ko-bets/.env ] && . /home/hermes/sites/ko-bets/.env
set +a
cd /home/hermes/sites/ko-bets
PY=$( [ -x /opt/hermes/.venv/bin/python3 ] && echo /opt/hermes/.venv/bin/python3 || command -v python3 )
exec "$PY" "$@"
