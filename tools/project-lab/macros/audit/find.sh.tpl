# FILE: tools/project-lab/macros/audit/find.sh.tpl | V1

echo "[OK] Comando Project Lab: find"
echo "[INFO] Entrada original: {{ORIGINAL_INPUT}}"
echo "[INFO] Archivo/patrón: {{FILE_PATTERN}}"
echo "[INFO] Término: {{TERM}}"
echo "[INFO] Líneas posteriores: {{LINES}}"
echo "[INFO] Búsqueda: case-insensitive"
echo "------------------------------------------------------------"

ORIGINAL_INPUT={{ORIGINAL_INPUT_SHELL}}
FILE_PATTERN={{FILE_PATTERN_SHELL}}
TERM={{TERM_SHELL}}
LINES={{LINES}}

find . \
  -type f \
  ! -path './vendor/*' \
  ! -path './node_modules/*' \
  ! -path './.git/*' \
  ! -path './storage/framework/cache/*' \
  ! -path './storage/logs/*' \
  ! -path './storage/app/private/*' \
  ! -path './documentos/auditoria/*' \
  ! -path './documentos/baks/*' \
  -print 2>/dev/null | while IFS= read -r FILE; do
  if [ "$FILE_PATTERN" != "*" ]; then
    echo "$FILE" | grep -qi -- "$FILE_PATTERN" || continue
  fi

  grep -Iq . "$FILE" 2>/dev/null || continue

  MATCH_LINES=$(grep -ni -- "$TERM" "$FILE" 2>/dev/null || true)

  if [ -z "$MATCH_LINES" ]; then
    continue
  fi

  LAST_END=0

  echo "$MATCH_LINES" | while IFS= read -r MATCH; do
    LINE=$(echo "$MATCH" | cut -d: -f1)

    if [ "$LINE" -le "$LAST_END" ]; then
      continue
    fi

    END=$((LINE + LINES))
    LAST_END=$END

    echo ""
    echo "[OK] Coincidencia: $FILE:$LINE"
    echo "------------------------------------------------------------"
    sed -n "${LINE},${END}p" "$FILE" | nl -ba -v "$LINE"
  done
done

echo ""
echo "[INFO] Fin de búsqueda Project Lab find."
