<div id="tab-tinker" class="tab-content">
    <form method="POST" id="tinkerForm">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="card">
            <div class="editor-header">
                <h4>Código Tinker</h4>
                <span class="shortcuts">Ctrl+Enter ejecutar • Ctrl+L limpiar</span>
            </div>

            <textarea name="code" id="code" placeholder="// Escribe tu código PHP aquí..."><?= htmlspecialchars($code) ?></textarea>

            <div class="snippets-bar" style="margin-top:14px;">
                <span>Comandos rápidos:</span>
                <span class="snippet-chip" onclick="insertSnippet('User::all();')">Users</span>
                <span class="snippet-chip" onclick="insertSnippet('DB::table(\'users\')->get();')">DB users</span>
                <span class="snippet-chip" onclick="insertSnippet('DB::table(\'tenants\')->get();')">Tenants</span>
                <span class="snippet-chip" onclick="insertSnippet('config(\'app.name\');')">Config app</span>
                <span class="snippet-chip" onclick="insertSnippet('app()->version();')">Laravel version</span>
                <span class="snippet-chip" onclick="insertSnippet('App\\\\Models\\\\')">App\Models\</span>
            </div>

            <div class="editor-actions" style="margin-top:12px;">
                <button type="button" onclick="runTinkerAjax()" class="run-btn">▶ Ejecutar</button>
                <button type="button" onclick="copyOutput()" class="secondary">📋 Copiar</button>
                <button type="button" onclick="exportOutput()" class="secondary">💾 Exportar</button>
                <button type="button" onclick="clearTinker()" class="danger">🗑️ Limpiar</button>
            </div>
            <div class="snippets-bar" style="margin-top:14px;">
                <span>Artisan:</span>
                <button type="button" onclick="runArtisanAjax('optimize:clear')" class="secondary small">
                    🧹 optimize:clear
                </button>
                <button type="button" onclick="runArtisanAjax('migrate')" class="secondary small">
                    🗂️ migrate
                </button>
                <button type="button" onclick="runArtisanAjax('db:seed')" class="secondary small">
                    🌱 db:seed
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

    <div class="card output-card" style="<?= empty($output) ? 'display:none;' : '' ?>">
        <div class="output-header">
            <span>📤 Resultado</span>
            <button onclick="copyOutput()" class="secondary small">Copiar</button>
        </div>
        <pre id="tinkerOutput"><?= htmlspecialchars($output) ?></pre>
    </div>
</div>