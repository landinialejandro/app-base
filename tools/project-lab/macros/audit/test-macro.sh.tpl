# FILE: tools/project-lab/macros/audit/test-macro.sh.tpl | V3

# PROJECT_LAB_MACRO_ARGS_MIN: 2
# PROJECT_LAB_MACRO_ARGS_MAX: 2

echo "[OK] Comando Project Lab: {{MACRO_NAME}}"
echo "[INFO] Entrada original: {{ORIGINAL_INPUT}}"
echo "[INFO] Argumentos recibidos: {{ARG_COUNT}}"
echo "------------------------------------------------------------"
echo "ARG_1={{ARG_1}}"
echo "ARG_2={{ARG_2}}"
echo "RAW_ARGS={{RAW_ARGS}}"