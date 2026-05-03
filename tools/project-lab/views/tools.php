<?php

// FILE: tools/project-lab/views/tools.php | V7

?>

<div id="tab-tools" class="tab-content active">
    <form method="POST" id="labToolsForm">
        <input type="hidden" name="from_clipboard" value="">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="card">
            <div class="editor-header" style="margin-bottom:12px;">
                <h4 style="margin:0;">Project Lab</h4>
                <span class="shortcuts">Clipboard, código, documentación, auditorías, Tinker y Artisan</span>
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
                            <button type="button" onclick="runTinkerFromClipboard()" class="primary">Tinker</button>
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
                        <div class="lab-tool-actions" style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:8px;">
                            <button type="button" onclick="runLabTool('code', false)" class="<?= $labToolActive === 'code' ? 'warning' : 'secondary' ?>">Código</button>
                            <button type="button" onclick="runLabTool('docs', false)" class="<?= $labToolActive === 'docs' ? 'warning' : 'secondary' ?>">Docs</button>
                            <button type="button" onclick="runLabTool('audit', false)" class="<?= $labToolActive === 'audit' ? 'warning' : 'secondary' ?>">Audit</button>
                            <button type="button" onclick="runTinkerAjax()" class="run-btn">Tinker</button>
                        </div>
                    </div>
                </details>
            </div>

            <div style="position:relative;">
                <button
                    type="button"
                    onclick="clearLabTools()"
                    title="Limpiar entrada"
                    aria-label="Limpiar entrada"
                    class="danger small"
                    style="position:absolute; top:8px; right:8px; z-index:2; min-width:auto; padding:4px 8px;"
                >
                    ×
                </button>

                <textarea
                    name="lab_input"
                    id="labInput"
                    placeholder="// Pegá aquí un archivo completo, TARGET, REEMPLAZAR EN, auditoría bash o código Tinker..."
                    style="min-height:160px; margin-bottom:0; padding-right:42px;"
                ><?= htmlspecialchars($labToolInput ?: $code) ?></textarea>
            </div>

            <details class="lab-snippets-details" style="margin-top:14px;">
                <summary>🧱 Formatos rápidos Project Lab</summary>

                <div class="lab-snippets-panel">
                    <span class="snippet-chip" onclick="insertLabSnippet('<?php\n\n// FILE: app/Support/Ejemplo.php | V1\n\n')">PHP FILE</span>
                    <span class="snippet-chip" onclick="insertLabSnippet('{{-- FILE: resources/views/ejemplo.blade.php | V1 --}}\n\n')">Blade FILE</span>
                    <span class="snippet-chip" onclick="insertLabSnippet('/* FILE: documentos/log/project-lab-test.css | V1 */\n\n')">CSS seguro</span>
                    <span class="snippet-chip" onclick="insertLabSnippet('// FILE: documentos/log/project-lab-test.js | V1\n\n')">JS seguro</span>
                    <span class="snippet-chip" onclick="insertLabSnippet('/* FILE: documentos/log/project-lab-test.js | V1 */\n\n')">JS block</span>
                    <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Http/Controllers/EjemploController.php :: show\n\npublic function show(): void\n{\n    // ...\n}')">TARGET ::</span>
                    <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Support/Ejemplo.php ++ nuevoMetodo\n\nprivate function nuevoMetodo(): void\n{\n    // ...\n}')">TARGET ++</span>
                    <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [contexto_fijo_proyecto_app_base]\n\n<<SECTION: NOMBRE EXACTO>>\nSECTION_VERSION: 00001\n\nContenido\n<<END SECTION>>')">Docs SECTION</span>
                    <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [documentos/log/test-sections.css]\n\n<<SECTION: COLORS>>\nbody {\n    color: red;\n}\n<<END SECTION>>')">CSS SECTION</span>
                    <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [documentos/log/test-sections.js]\n\n<<SECTION: INIT>>\nconsole.log(\'section updated\');\n<<END SECTION>>')">JS SECTION</span>
                </div>
            </details>

            <div class="snippets-bar" style="margin-top:14px;">
                <span>Tinker:</span>
                <span class="snippet-chip" onclick="insertLabSnippet('return User::all();')">Users</span>
                <span class="snippet-chip" onclick="insertLabSnippet('return DB::table(\'users\')->get();')">DB users</span>
                <span class="snippet-chip" onclick="insertLabSnippet('return DB::table(\'tenants\')->get();')">Tenants</span>
                <span class="snippet-chip" onclick="insertLabSnippet('return config(\'app.name\');')">Config app</span>
                <span class="snippet-chip" onclick="insertLabSnippet('return app()->version();')">Laravel version</span>
                <span class="snippet-chip" onclick="insertLabSnippet('App\\\\Models\\\\')">App\Models\</span>
            </div>

            <div class="snippets-bar" style="margin-top:14px;">
                <span>Artisan:</span>

                <button type="button" onclick="runArtisanAjax('optimize:clear')" class="secondary small">
                    🧹 optimize:clear
                </button>

                <button type="button" onclick="runArtisanAjax('route:list')" class="secondary small">
                    🧭 route:list
                </button>

                <button type="button" onclick="runArtisanAjax('about')" class="secondary small">
                    ℹ️ about
                </button>

                <button type="button" onclick="runArtisanAjax('migrate:status')" class="secondary small">
                    🗂️ migrate:status
                </button>

                <button type="button" onclick="runArtisanAjax('queue:work --stop-when-empty --tries=1')" class="success small">
                    ⚙️ procesar cola
                </button>

                <button
                    type="button"
                    onclick="showConfirmDialog('⚠️ Escribe BORRAR para confirmar:', () => runArtisanAjax('migrate:fresh --seed'))"
                    class="warning small"
                >
                    🔥 fresh + seed
                </button>
            </div>
        </div>
    </form>

    <?php if (! empty($labToolOutput) || ! empty($output)) { ?>
        <div class="card output-card">
            <div class="output-header">
                <span>📤 Salida Project Lab</span>
                <button onclick="copyProjectConsoleOutput()" class="secondary small">Copiar</button>
            </div>
            <pre id="labOutput"><?= htmlspecialchars($labToolOutput ?: $output) ?></pre>
        </div>
    <?php } ?>
</div>