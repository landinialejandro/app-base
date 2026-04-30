<?php // FILE: tools/project-lab/views/tools.php | V2?>

<div id="tab-tools" class="tab-content" style="display:none;">
    <form method="POST" id="labToolsForm">
        <input type="hidden" name="from_clipboard" value="">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="card">
            <div class="editor-header">
                <h4>Herramientas Lab</h4>
                <span class="shortcuts">Actualización embebida de código y documentación</span>
            </div>

            <div  class="card lab-tool-card lab-tool-card--clipboard" data-title="Desde clipboard">
                <div style="display:flex; justify-content:space-evenly; margin-top:12px;" class="lab-tool-actions">
                    <button type="button" onclick="runLabTool('code', true)" class="<?= $labToolActive === 'code' ? 'warning' : 'secondary' ?>">
                        📋 Código
                    </button>
                    <button type="button" onclick="runLabTool('docs', true)" class="<?= $labToolActive === 'docs' ? 'warning' : 'secondary' ?>">
                        📋 Docs
                    </button>
                    <button type="button" onclick="runLabTool('audit', true)" class="<?= $labToolActive === 'audit' ? 'warning' : 'secondary' ?>">
                        🔍 Auditoría
                    </button>
                    <button type="button" onclick="clearLabTools()" class="danger">
                        🗑️ Limpiar
                    </button>
                </div>
            </div>

            <div class="card lab-tool-card lab-tool-card--direct" data-title="Desde textarea">
                <div style="display:flex; justify-content:space-evenly; margin-top:12px;" class="lab-tool-actions">
                    <button type="button" onclick="runLabTool('code', false)" class="<?= $labToolActive === 'code' ? 'warning' : 'secondary' ?>">
                        💻 Actualizar código
                    </button>

                    <button type="button" onclick="runLabTool('docs', false)" class="<?= $labToolActive === 'docs' ? 'warning' : 'secondary' ?>">
                        📄 Actualizar docs
                    </button>

                    <button type="button" onclick="runLabTool('audit', false)" class="<?= $labToolActive === 'audit' ? 'warning' : 'secondary' ?>">
                        🔍 Ejecutar auditoría
                    </button>
                </div>

                <textarea
                    name="lab_input"
                    id="labInput"
                    placeholder="// Pegá aquí un archivo PHP/Blade/CSS/JS completo, métodos TARGET, bloques REEMPLAZAR EN o auditorías bash..."
                    style="min-height:180px;"
                ><?= htmlspecialchars($labToolInput) ?></textarea>

                <div class="lab-snippets">
                    <span class="lab-snippets-label">Formatos:</span>

                    <details>
                        <summary>Archivos</summary>
                        <div class="lab-snippets-panel">
                            <span class="snippet-chip" onclick="insertLabSnippet('<?php\n\n// FILE: app/Support/Ejemplo.php | V1\n\n')">PHP FILE</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('{{-- FILE: resources/views/ejemplo.blade.php | V1 --}}\n\n')">Blade FILE</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('/* FILE: documentos/log/project-lab-test.css | V1 */\n\n')">CSS seguro</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('// FILE: documentos/log/project-lab-test.js | V1\n\n')">JS seguro</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('/* FILE: documentos/log/project-lab-test.js | V1 */\n\n')">JS block seguro</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [documentos/log/test-sections.css]\n<<SECTION: COLORS>>\nbody {\n    color: red;\n}\n<<END SECTION>>')">CSS SECTION</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [documentos/log/test-sections.js]\n<<SECTION: INIT>>\nconsole.log(\'section updated\');\n<<END SECTION>>')">JS SECTION</span>
                        </div>
                    </details>

                    <details>
                        <summary>Métodos</summary>
                        <div class="lab-snippets-panel">
                            <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Http/Controllers/EjemploController.php :: show\n\npublic function show(): void\n{\n    // ...\n}')">TARGET replace</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Support/Ejemplo.php ++ nuevoMetodo\n\nprivate function nuevoMetodo(): void\n{\n    // ...\n}')">TARGET add</span>
                        </div>
                    </details>

                    <details>
                        <summary>Docs</summary>
                        <div class="lab-snippets-panel">
                            <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [contexto_fijo_proyecto_app_base]\n<<SECTION: NOMBRE EXACTO>>\nSECTION_VERSION: 00001\n\nContenido\n<<END SECTION>>')">Docs SECTION</span>
                        </div>
                    </details>
                </div>
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