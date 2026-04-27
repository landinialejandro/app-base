#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "Aplicando actualizaciones de documentos desde portapapeles..."
xclip -selection clipboard -o | php ./tools/aplicar-actualizaciones-docs.php
echo "Proceso finalizado."