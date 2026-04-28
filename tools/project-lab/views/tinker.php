<div id="tab-tinker" class="tab-content">
    <form method="POST" id="tinkerForm">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="card">
            <div class="editor-header">
                <h4>Código Tinker</h4>
                <span class="shortcuts">Ctrl+Enter ejecutar • Ctrl+L limpiar</span>
            </div>
            <textarea name="code" id="code" placeholder="// Escribe tu código PHP aquí..."><?= htmlspecialchars($code) ?></textarea>
            
            <div class="snippets-bar">
                <span>Quick:</span>
                <span class="snippet-chip" onclick="insertSnippet('User::all()')">User::all()</span>
                <span class="snippet-chip" onclick="insertSnippet('DB::table(\'users\')->get()')">DB::table()</span>
                <span class="snippet-chip" onclick="insertSnippet('App\\Models\\')">App\Models\</span>
                <span class="snippet-chip" onclick="insertSnippet('config(\'app.name\')')">config()</span>
            </div>
            
            <div class="editor-actions">
                <button type="button" onclick="runTinkerAjax()" class="run-btn">▶ EJECUTAR</button>
                <button type="button" onclick="copyOutput()" class="secondary">📋 Copiar</button>
                <button type="button" onclick="exportOutput()" class="secondary">💾 Exportar</button>
                <button type="button" onclick="clearTinker()" class="danger">🗑️ Limpiar</button>
            </div>
        </div>
    </form>
    
    <?php if ($output) { ?>
    <div class="card output-card">
        <div class="output-header">
            <span>📤 Resultado</span>
            <button onclick="copyOutput()" class="secondary small">Copiar</button>
        </div>
        <pre id="tinkerOutput"><?= htmlspecialchars($output) ?></pre>
    </div>
    <?php } ?>
</div>