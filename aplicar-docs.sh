#!/usr/bin/env bash

echo "Aplicando actualizaciones de documentos desde portapapeles..."
xclip -selection clipboard -o | php aplicar-actualizaciones-docs.php
echo "Proceso finalizado."