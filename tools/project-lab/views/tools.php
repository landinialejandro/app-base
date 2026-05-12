<?php

// FILE: tools/project-lab/views/tools.php | V3

?>

<div class="editor-container">
    <form method="POST" id="labToolsForm">
        <input type="hidden" name="from_clipboard" value="">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="card">
            <div class="editor-header" style="margin-bottom:12px;">
                <h4 style="margin:0;">Project Lab</h4>
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
                        <div class="lab-tool-actions lab-tool-actions--compact">
                            <button type="button" onclick="runLabTool('code', true)" class="primary">Código</button>
                            <button type="button" onclick="runLabTool('docs', true)" class="success">Docs</button>
                            <button type="button" onclick="runLabTool('audit', true)" class="warning">Auditoría</button>
                            <button type="button" onclick="runTinkerFromClipboard()" class="primary">Tinker</button>
                            <button
                                type="button"
                                class="secondary"
                                onclick="runProjectAction({
                                    action: 'ajax_ai_prompt',
                                    loading: '⏳ Consultando IA desde clipboard...',
                                    data: {
                                        model: document.getElementById('localAiModel') ? document.getElementById('localAiModel').value : '',
                                        from_clipboard: '1',
                                        prompt: '',
                                        console_output: document.getElementById('projectConsoleOutput') ? document.getElementById('projectConsoleOutput').innerText : ''
                                    }
                                })"
                            >IA</button>
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
                        <div class="lab-tool-actions lab-tool-actions--compact">
                            <button type="button" onclick="runLabTool('code', false)" class="primary">Código</button>
                            <button type="button" onclick="runLabTool('docs', false)" class="success">Docs</button>
                            <button type="button" onclick="runLabTool('audit', false)" class="warning">▶ Audit</button>
                            <button type="button" onclick="runTinkerAjax()" class="primary">Tinker</button>
                            <button
                                type="button"
                                class="secondary"
                                onclick="runProjectAction({
                                    action: 'ajax_ai_prompt',
                                    loading: '⏳ Consultando IA ...',
                                    data: {
                                        model: document.getElementById('localAiModel') ? document.getElementById('localAiModel').value : '',
                                        from_clipboard: '0',
                                        prompt: document.getElementById('labInput') ? document.getElementById('labInput').value : '',
                                        console_output: document.getElementById('projectConsoleOutput') ? document.getElementById('projectConsoleOutput').innerText : ''
                                    }
                                })"
                            >IA</button>
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
                    placeholder="// Pegá aquí un archivo completo, TARGET, REEMPLAZAR EN, auditoría bash, código Tinker o consulta IA local..."
                    style="min-height:160px; margin-bottom:0; padding-right:42px;"
                ><?= htmlspecialchars($labToolInput ?: $code) ?></textarea>
            </div>

            <div class="quick-formats-bar" style="margin-top:14px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span class="quick-formats-label">Atajos</span>

                    <details class="quick-format-menu">
                        <summary>Project Lab ▾</summary>
                        <div class="quick-format-panel">
                            <span class="snippet-chip" onclick="insertLabSnippet('&lt;?php\n\n// FILE: app/Support/Ejemplo.php | V1\n\n')">PHP FILE</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('{{-- FILE: resources/views/ejemplo.blade.php | V1 --}}\n\n')">Blade FILE</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('/* FILE: documentos/log/project-lab-test.css | V1 */\n\n')">CSS FILE</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('// FILE: documentos/log/project-lab-test.js | V1\n\n')">JS FILE</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Http/Controllers/EjemploController.php :: show\n\npublic function show(): void\n{\n    // ...\n}')">PHP TARGET ::</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('// TARGET: app/Support/Ejemplo.php ++ nuevoMetodo\n\nprivate function nuevoMetodo(): void\n{\n    // ...\n}')">PHP TARGET ++</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [contexto_fijo_proyecto_app_base]\n\n<<SECTION: NOMBRE EXACTO>>\nSECTION_VERSION: 00001\n\nContenido\n<<END SECTION>>')">DOC SECTION</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [documentos/log/test-sections.css]\n\n<<SECTION: COLORS>>\nbody {\n    color: red;\n}\n<<END SECTION>>')">CSS SECTION</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('REEMPLAZAR EN: [documentos/log/test-sections.js]\n\n<<SECTION: INIT>>\nconsole.log(\'section updated\');\n<<END SECTION>>')">JS SECTION</span>
                        </div>
                    </details>

                    <details class="quick-format-menu">
                        <summary>Tinker ▾</summary>
                        <div class="quick-format-panel">
                            <span class="snippet-chip" onclick="insertLabSnippet('use App\\\\Models\\\\User;\n\nreturn User::query()->first();')">User first</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('use App\\\\Models\\\\Tenant;\n\nreturn Tenant::query()->first();')">Tenant first</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('return DB::table(\'users\')->get();')">DB users</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('return DB::table(\'tenants\')->get();')">DB tenants</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('return config(\'app.name\');')">Config app</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('return app()->version();')">Laravel version</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('return auth()->user();')">Auth user</span>
                            <span class="snippet-chip" onclick="insertLabSnippet('return app()->bound(\'tenant\') ? app(\'tenant\') : null;')">Tenant bound</span>
                        </div>
                    </details>

                    <details class="quick-format-menu">
                        <summary>Artisan ▾</summary>
                        <div class="quick-format-panel quick-format-panel--artisan">
                            <button type="button" onclick="runArtisanAjax('optimize:clear')" class="secondary small">optimize:clear</button>
                            <button type="button" onclick="runArtisanAjax('route:list')" class="secondary small">route:list</button>
                            <button type="button" onclick="runArtisanAjax('about')" class="secondary small">about</button>
                            <button type="button" onclick="runArtisanAjax('migrate:status')" class="secondary small">migrate:status</button>
                            <button type="button" onclick="runArtisanAjax('queue:work --stop-when-empty --tries=1')" class="success small">procesar cola</button>
                            <button type="button" onclick="showConfirmDialog('⚠️ Escribe BORRAR para confirmar:', () => runArtisanAjax('migrate:fresh --seed'))" class="danger small">migrate:fresh --seed</button>
                        </div>
                    </details>
                </div>

                <div style="display:flex; align-items:center; gap:8px; margin-left:auto;">
                    <label for="localAiModel" style="font-size:12px; color:var(--muted); white-space:nowrap;">Modelo IA</label>
                    <select id="localAiModel" name="local_ai_model" style="max-width:260px;">
                        <option value="ollama:qwen2.5:1.5b">Ollama · qwen2.5:1.5b</option>
                        <option value="ollama:qwen2.5:3b">Ollama · qwen2.5:3b</option>
                        <option value="gemini:gemini-2.5-flash">Gemini · 2.5 Flash</option>
                    </select>
                </div>
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