<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Lab v8 - <?= htmlspecialchars($systemInfo['laravel_version'] ?? 'Laravel') ?></title>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime(__DIR__.'/../assets/css/app.css') ?>">
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__.'/../assets/js/app.js') ?>" defer></script>
</head>
<body>
    <header class="project-header">
        <div>
            <h1>🧪 Project Lab <small>v8.0</small></h1>
            
        </div>

        <div class="header-status">
            <span class="dot"></span>
            <span>DB: <?= htmlspecialchars($systemInfo['db_database'] ?? '-') ?></span>
            <span>|</span>
            <span>PHP: <?= htmlspecialchars($systemInfo['php_version'] ?? '-') ?></span>
            <span>|</span>
            <span>RAM: <?= htmlspecialchars((string) ($systemInfo['memory_usage'] ?? '-')) ?>MB</span>
        </div>
    </header>

    <div class="dashboard-grid">
        <aside class="sidebar">
            <div class="card">
                <div class="header-info">
                    <span>Rate: <?= htmlspecialchars((string) ($rateLimitData['count'] ?? 0)) ?>/300</span>
                    <span>•</span>
                    <span>Reset: <?= isset($rateLimitData['reset']) ? date('H:i', $rateLimitData['reset']) : '-' ?></span>
                </div>
                <button type="button" class="tab-btn active" onclick="showTab('tools')">🧰 Project Lab</button>
                <button type="button" class="tab-btn" onclick="showTab('database')">🗄️ Base de Datos</button>
                <button type="button" class="tab-btn" onclick="showTab('routes')">🔗 Rutas (<?= count($routes ?? []) ?>)</button>
                <button type="button" class="tab-btn" onclick="showTab('monitor')">📊 Monitor</button>
                <button type="button" class="tab-btn" onclick="showTab('help')">❓ Ayuda</button>
                <button type="button" class="tab-btn" onclick="showTab('icons')">🎨 Íconos</button>
                <button type="button" class="tab-btn" onclick="showTab('components')">🧩 Componentes</button>
                <button type="button" class="tab-btn" onclick="showTab('sections')">🧱 Secciones</button>
            </div>
        </aside>

        <main>
            <?php require __DIR__.'/tools.php'; ?>
            <?php require __DIR__.'/database.php'; ?>
            <?php require __DIR__.'/routes.php'; ?>
            <?php require __DIR__.'/help.php'; ?>
            <?php require __DIR__.'/icons.php'; ?>
            <?php require __DIR__.'/components.php'; ?>
            <?php require __DIR__.'/monitor.php'; ?>
            <?php require __DIR__.'/sections.php'; ?>

            <div class="card output-card" id="projectConsoleCard">
                <div class="output-header">
                    <span>Consola Project Lab</span>
                    <div class="output-actions">
                        <button type="button" onclick="copyProjectConsoleOutput()" class="secondary small">Copiar</button>
                        <button type="button" onclick="saveProjectConsoleOutput()" class="success small">Guardar</button>
                        <button type="button" onclick="findProjectMethodContext()" class="secondary small">Buscar método</button>
                        <button type="button" onclick="clearProjectConsoleOutput()" class="danger small">Borrar</button>
                    </div>
                </div>
                <pre id="projectConsoleOutput" class="project-console-empty">Sin salida todavía.</pre>
            </div>
        </main>
    </div>
</body>
</html>