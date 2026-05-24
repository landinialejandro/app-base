#!/usr/bin/env bash
# FILE: tools/project-lab.sh | V4

set -e

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

MODE="${1:-local}"

if [ "$MODE" = "mac" ]; then
    PROJECT_LAB_HOST="${PROJECT_LAB_HOST:-192.168.0.200}"
    PROJECT_LAB_OPEN_BROWSER="${PROJECT_LAB_OPEN_BROWSER:-0}"
fi

HOST="${PROJECT_LAB_HOST:-127.0.0.1}"
PORT="${PROJECT_LAB_PORT:-8787}"
URL="http://${HOST}:${PORT}"
LOG="/tmp/project-lab.log"
WORKERS="${PROJECT_LAB_WORKERS:-4}"
OPEN_BROWSER="${PROJECT_LAB_OPEN_BROWSER:-1}"

echo "[INFO] Iniciando Project Lab..."
echo "[INFO] Modo: $MODE"
echo "[INFO] Host: $HOST"
echo "[INFO] Puerto: $PORT"
echo "[INFO] Workers PHP CLI server: $WORKERS"

PHP_CLI_SERVER_WORKERS="$WORKERS" php -S "${HOST}:${PORT}" -t tools/project-lab >"$LOG" 2>&1 &
SERVER_PID=$!

sleep 1

echo "[OK] Servidor: $URL"
echo "[OK] PID principal: $SERVER_PID"
echo "[INFO] Log: $LOG"

if [ "$OPEN_BROWSER" = "1" ]; then
    if command -v firefox >/dev/null 2>&1; then
        firefox "$URL" >/dev/null 2>&1 &
    elif command -v xdg-open >/dev/null 2>&1; then
        xdg-open "$URL" >/dev/null 2>&1 &
    fi
fi

wait "$SERVER_PID"