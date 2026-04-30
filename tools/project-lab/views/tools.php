<?php // FILE: tools/project-lab/views/tools.php | V6?>

<div id="tab-tools" class="tab-content" style="display:none;">
    <form method="POST" id="labToolsForm">
        <input type="hidden" name="from_clipboard" value="">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="card">
            <div class="editor-header" style="margin-bottom:12px;">
                <h4 style="margin:0;">Herramientas Lab</h4>
                <span class="shortcuts">Actualización de código, documentación y auditorías</span>
            </div>

            <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; margin-bottom:12px;">
                <details open class="card lab-tool-card lab-tool-card--clipboard" style="margin:0;">
                    <summary>
                        <span class="lab-tool-card-title">
                            <span class="lab-tool-card-arrow">▾</span>
                            <strong>📋 Desde clipboard</strong>
                        </span>
                    </summary>

                    <div class="lab-tool-card-body">
                        <div class="lab-tool-actions" style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:8px;">
                            <button type="button" onclick="runLabTool('code', true)" class="primary">Código</button>
                            <button type="button" onclick="runLabTool('docs', true)" class="success">Docs</button>
                            <button type="button" onclick="runLabTool('audit', true)" class="warning">Auditoría</button>
                            <button type="button" onclick="clearLabTools()" class="danger">Limpiar</button>
                        </div>
                    </div>
                </details>

                <details open class="card lab-tool-card lab-tool-card--direct" style="margin:0;">
                    <summary>
                        <span class="lab-tool-card-title">
                            <span class="lab-tool-card-arrow">▾</span>
                            <strong>⌨️ Desde textarea</strong>
                        </span>
                    </summary>

                    <div class="lab-tool-card-body">
                        <div class="lab-tool-actions" style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:8px;">
                            <button type="button" onclick="runLabTool('code', false)" class="<?= $labToolActive === 'code' ? 'warning' : 'secondary' ?>">Código</button>
                            <button type="button" onclick="runLabTool('docs', false)" class="<?= $labToolActive === 'docs' ? 'warning' : 'secondary' ?>">Docs</button>
                            <button type="button" onclick="runLabTool('audit', false)" class="<?= $labToolActive === 'audit' ? 'warning' : 'secondary' ?>">Audit</button>
                        </div>

                        <details class="lab-snippets-details">
                            <summary>🧱 Formatos rápidos</summary>

                            <div class="lab-snippets-panel">
                                <span class="snippet-chip" onclick="insertLabSnippet('<?php\n\n// FILE: app/Support/Ejemplo.php | V1\n\n')">PHP FILE</span>
                                <span class="snippet-chip" onclick="insertLabSnippet('{{-- FILE: resources/views/ejemplo.blade.php | V1 --}}\n\n')">Blade FILE</span>
                                <span class="snippet-chip" onclick="insertLabSnippet('/* FILE: documentos/log/project-lab-test.css | V1 */\n\n')">CSS seguro</span>
                                <span class="snippet-chip" onclick="insertLabSnippet('// FILE: documentos/log/project-lab-test.js | V1\n\n')">JS seguro</span>
                                <span class="snippet-chip" onclick="insertLabSnippet('/* FILE: documentos/log/project-lab-test.js | V1 */\n\n')">JS block</span>
                                <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Http/Controllers/EjemploController.php :: show\n\npublic function show(): void\n{\n    // ...\n}')">TARGET ::</span>
                                <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Support/Ejemplo.php ++ nuevoMetodo\n\nprivate function nuevoMetodo(): void\n{\n    // ...\n}')">TARGET ++</span>
                                <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [contexto_fijo_proyecto_app_base]\n<<SECTION: NOMBRE EXACTO>>\nSECTION_VERSION: 00001\n\nContenido\n<<END SECTION>>')">Docs SECTION</span>
                                <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [documentos/log/test-sections.css]\n<<SECTION: COLORS>>\nbody {\n    color: red;\n}\n<<END SECTION>>')">CSS SECTION</span>
                                <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [documentos/log/test-sections.js]\n<<SECTION: INIT>>\nconsole.log(\'section updated\');\n<<END SECTION>>')">JS SECTION</span>
                            </div>
                        </details>
                    </div>
                </details>
            </div>

            <textarea
                name="lab_input"
                id="labInput"
                placeholder="// Pegá aquí un archivo completo, TARGET, REEMPLAZAR EN o auditoría bash..."
                style="min-height:105px; margin-bottom:0;"
            ><?= htmlspecialchars($labToolInput) ?></textarea>
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