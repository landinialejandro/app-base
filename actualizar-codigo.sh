#!/usr/bin/env bash

echo "Aplicando actualizaciones de código desde portapapeles..."
xclip -selection clipboard -o | php ./normalizar_codigo_version.php
echo "Proceso finalizado."