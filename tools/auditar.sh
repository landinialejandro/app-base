#!/bin/bash

# ============================================
# EJECUTOR DE COMANDOS DESDE CLIPBOARD
# Project Lab - Autodetecta raíz del proyecto
# ============================================

# Detectar la raíz del proyecto Laravel
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Buscar artisan hacia arriba
PROJECT_ROOT="$SCRIPT_DIR"
for i in {1..5}; do
    if [ -f "$PROJECT_ROOT/artisan" ]; then
        break
    fi
    PROJECT_ROOT="$(dirname "$PROJECT_ROOT")"
done

# Verificar que encontramos el proyecto
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "❌ Error: No se encontró un proyecto Laravel (artisan no encontrado)"
    echo "📂 Buscando desde: $SCRIPT_DIR"
    exit 1
fi

# Cambiar al directorio del proyecto
cd "$PROJECT_ROOT" || exit 1

echo "========================================="
echo "🔍 EJECUTANDO AUDITORÍA"
echo "========================================="
echo "📂 Proyecto: $PROJECT_ROOT"
echo ""

# Verificar si hay comandos en el portapapeles
if command -v xclip &> /dev/null; then
    CLIPBOARD=$(xclip -selection clipboard -o 2>/dev/null)
elif command -v pbpaste &> /dev/null; then
    CLIPBOARD=$(pbpaste 2>/dev/null)
elif command -v wl-paste &> /dev/null; then
    CLIPBOARD=$(wl-paste 2>/dev/null)
else
    echo "❌ No se encontró herramienta de clipboard"
    echo "💡 Instala xclip: sudo apt-get install xclip"
    exit 1
fi

if [ -z "$CLIPBOARD" ]; then
    echo "❌ El portapapeles está vacío"
    echo "💡 Copia los comandos al portapapeles primero"
    exit 1
fi

echo "📋 Comandos detectados en clipboard"
echo ""

# Ejecutar los comandos del portapapeles directamente
bash -c "$CLIPBOARD"

echo ""
echo "========================================="
echo "✅ Auditoría completada"
echo "========================================="