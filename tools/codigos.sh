#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "Aplicando actualizaciones de código desde portapapeles..."
xclip -selection clipboard -o | php ./tools/aplicar-actualizaciones-codigo.php
echo "Proceso finalizado."