<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Lab v8 - <?= $systemInfo['laravel_version'] ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="project-header">
        <div>
            <h1>🧪 Project Lab <small>v8.0</small></h1>
            <div class="header-info">
                <span>Rate: <?= $rateLimitData['count'] ?>/50</span>
                <span>•</span>
                <span>Reset: <?= date('H:i', $rateLimitData['reset']) ?></span>
            </div>
        </div>
        <div class="header-status">
            <span class="dot"></span>
            <span>DB: <?= $systemInfo['db_database'] ?></span>
            <span>|</span>
            <span>PHP: <?= $systemInfo['php_version'] ?></span>
            <span>|</span>
            <span>RAM: <?= $systemInfo['memory_usage'] ?>MB</span>
        </div>
    </header>

    <div class="dashboard-grid">
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Menú -->
            <div class="card">
                <h4>💻 Menú</h4>
                <button class="tab-btn active" onclick="showTab('tinker')">🧪 Editor Tinker</button>
                <button class="tab-btn" onclick="showTab('database')">🗄️ Base de Datos</button>
                <button class="tab-btn" onclick="showTab('routes')">🔗 Rutas (<?= count($routes) ?>)</button>
                <button class="tab-btn" onclick="showTab('monitor')">📊 Monitor</button>
                <button class="tab-btn" onclick="showTab('tools')">🧰 Herramientas Lab</button>
            </div>

            <!-- Historial -->
            <?php if (! empty($history)) { ?>
            <div class="card">
                <h4>📝 Historial</h4>
                <div class="history-list">
                    <?php foreach ($history as $item) { ?>
                    <div class="history-item" 
                         onclick="insertCode(<?= htmlspecialchars(json_encode($item['code'])) ?>); showTab('tinker');">
                        <?= $item['success'] ? '✅' : '❌' ?> 
                        <?= htmlspecialchars($item['preview']) ?>
                        <small><?= $item['timestamp'] ?></small>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

            <!-- Scripts -->
            <div class="card">
                <h4>📜 Scripts</h4>
                <div class="btn-group">
                    <button onclick="runScript('docs.sh')" class="secondary">📄 Docs.sh</button>
                    <button onclick="runScript('codigos.sh')" class="secondary">💻 Codigos.sh</button>
                    <button onclick="runScript('auditar.sh')" class="secondary" style="grid-column: 1 / -1;">🔍 Auditar.sh</button>
                </div>
                <div id="scriptStatus"></div>
            </div>

            <!-- Artisan -->
            <div class="card">
                <h4>⚡ Artisan</h4>
                <form method="POST" class="artisan-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <button name="artisan" value="optimize:clear" class="secondary">🧹 Limpiar</button>
                    <button name="artisan" value="migrate" class="secondary">🗂️ Migrar</button>
                    <button name="artisan" value="db:seed" class="secondary">🌱 Seed</button>
                    <hr>
                    <button name="artisan" value="migrate:fresh --seed" 
                            class="warning" 
                            data-danger="true"
                            data-confirm="⚠️ Escribe 'BORRAR' para confirmar:">
                        🔥 Fresh + Seed
                    </button>
                </form>
            </div>

            <!-- Generador de Modelos -->
            <div class="card">
                <h4 style="margin:0 0 12px 0;">🚀 Generador Rápido</h4>
                <div class="generator-container">
                    <input 
                        type="text" 
                        id="modelName" 
                        placeholder="Nombre del Modelo (ej: Product)" 
                        class="generator-input"
                        autocomplete="off"
                    >
                    <button 
                        onclick="generateModel()" 
                        class="warning generator-btn"
                        title="Crear Modelo con Migración, Factory y Seeder"
                    >
                        ⚡ Crear -mfs
                    </button>
                </div>
                <div id="genStatus" class="generator-status"></div>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main>
            <?php require 'tinker.php'; ?>
            <?php require 'tools.php'; ?>
            <?php require 'database.php'; ?>
            <?php require 'routes.php'; ?>
            <?php require 'monitor.php'; ?>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>