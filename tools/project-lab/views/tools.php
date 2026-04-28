<div id="tab-tools" class="tab-content" style="display:none;">
    <form method="POST" id="labToolsForm">
        <input type="hidden" name="from_clipboard" value="">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="card">
            <div class="editor-header">
                <h4>Herramientas Lab</h4>
                <span class="shortcuts">Actualización embebida de código y documentación</span>
            </div>

            <div style="display:flex; gap:10px; margin-bottom:10px; flex-wrap:wrap;">
                <button type="button" onclick="runLabTool('code', false)" class="<?= $labToolActive === 'code' ? 'warning' : 'secondary' ?>">
                    💻 Aplicar código
                </button>

                <button type="button" onclick="runLabTool('docs', false)" class="<?= $labToolActive === 'docs' ? 'warning' : 'secondary' ?>">
                    📄 Aplicar docs
                </button>
            </div>

            <div style="display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
                <button type="button" onclick="runLabTool('code', true)" class="secondary">
                    📋💻 Código desde clipboard
                </button>

                <button type="button" onclick="runLabTool('docs', true)" class="secondary">
                    📋📄 Docs desde clipboard
                </button>

                <button type="button" onclick="runLabTool('audit', true)" class="<?= $labToolActive === 'audit' ? 'warning' : 'secondary' ?>">
                    🔍 Auditoría desde clipboard
                </button>
            </div>

            <textarea
                name="lab_input"
                id="labInput"
                placeholder="// Pegá aquí un archivo PHP/Blade completo o bloques REEMPLAZAR EN..."
                style="min-height:180px;"
            ><?= htmlspecialchars($labToolInput) ?></textarea>

            <div class="snippets-bar">
                <span>Formatos:</span>
                <span class="snippet-chip" onclick="insertLabSnippet('<?php\n\n// FILE: app/Support/Ejemplo.php | V1\n\n')">PHP FILE</span>
                <span class="snippet-chip" onclick="insertLabSnippet('{{-- FILE: resources/views/ejemplo.blade.php | V1 --}}\n\n')">Blade FILE</span>
                <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [contexto_fijo_proyecto_app_base]\n<<SECTION: NOMBRE EXACTO>>\nSECTION_VERSION: 00001\n\nContenido\n<<END SECTION>>')">Docs SECTION</span>
                <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Http/Controllers/EjemploController.php :: show\n\npublic function show(): void\n{\n    // ...\n}')">TARGET replace</span>
                <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Support/Ejemplo.php ++ nuevoMetodo\n\nprivate function nuevoMetodo(): void\n{\n    // ...\n}')">TARGET add</span>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:12px;">
                <button type="button" onclick="clearLabTools()" class="danger">
                    🗑️ Limpiar
                </button>
            </div>
        </div>
    </form>

    <?php if (! empty($labToolOutput)) { ?>
        <div class="card output-card">
            <div class="output-header">
                <span>📤 Salida Herramientas Lab</span>
                <button onclick="copyLabOutput()" class="secondary small">Copiar</button>
            </div>
            <pre id="labOutput"><?= htmlspecialchars($labToolOutput) ?></pre>
        </div>
    <?php } ?>
</div>