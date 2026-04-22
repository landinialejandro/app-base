#!/usr/bin/env bash

echo "Aplicando actualizaciones de código desde portapapeles..."
xclip -selection clipboard -o | php ./aplicar-actualizaciones-codigo.php
echo "Proceso finalizado."