#!/usr/bin/env bash
# FILE: tools/project-lab.sh | V3

set -e

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

URL="http://127.0.0.1:8787"
LOG="/tmp/project-lab.log"
WORKERS="${PROJECT_LAB_WORKERS:-4}"

echo "[INFO] Iniciando Project Lab..."
echo "[INFO] Workers PHP CLI server: $WORKERS"

PHP_CLI_SERVER_WORKERS="$WORKERS" php -S 127.0.0.1:8787 -t tools/project-lab >"$LOG" 2>&1 &
SERVER_PID=$!

sleep 1

echo "[OK] Servidor: $URL"
echo "[OK] PID principal: $SERVER_PID"
echo "[INFO] Log: $LOG"

if command -v firefox >/dev/null 2>&1; then
    firefox "$URL" >/dev/null 2>&1 &
elif command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$URL" >/dev/null 2>&1 &
fi

wait "$SERVER_PID"