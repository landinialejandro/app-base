<?php
/**
 * PROJECT LAB V5 - Dashboard de Arquitectura y Control
 * Compatible con Laravel 12.x
 */

// 1. CARGA DE ENTORNO (Subimos 2 niveles desde tools/dash-lab/ a la raíz)
$projectRoot = dirname(__DIR__, 2);
require $projectRoot.'/vendor/autoload.php';

// Iniciamos la app
$app = require_once $projectRoot.'/bootstrap/app.php';

// Registro manual del Kernel de Consola para evitar el error de "Kernel not found" en v12
$app->singleton(
    Kernel::class,
    Illuminate\Foundation\Console\Kernel::class
);

$app->make(Kernel::class)->bootstrap();

// Importamos las Fachadas necesarias
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Seguridad: Solo entorno local
if (config('app.env') !== 'local') {
    exit('Acceso denegado: Project Lab solo local.');
}

// 2. LÓGICA DE PROCESAMIENTO
$logFile = $projectRoot.'/documentos/log/project-lab.log';
if (! is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0775, true);
}

$output = '';
$code = $_POST['code'] ?? '';

// Acción: Ejecutar Tinker con Captura SQL
if (isset($_POST['run']) && ! empty($code)) {
    $wrappedCode = "
        \DB::enableQueryLog();
        \$start = microtime(true);
        try {
            {$code}
        } catch (\Throwable \$e) {
            echo 'ERROR: ' . \$e->getMessage();
        }
        \$queries = \DB::getQueryLog();
        \$time = round((microtime(true) - \$start) * 1000, 2);
        echo \"\n\n--- SQL DEPURACIÓN (\" . count(\$queries) . \" consultas, {\$time}ms) ---\n\";
        foreach(\$queries as \$q) {
            echo \"[\".\$q['time'].\"ms] \".\$q['query'].\" | Binds: \".json_encode(\$q['bindings']).\"\n\";
        }
    ";
    $command = 'cd '.escapeshellarg($projectRoot).' && php artisan tinker --execute='.escapeshellarg($wrappedCode).' 2>&1';
    $output = shell_exec($command);
    file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] TINKER EXEC:\n$output\n\n", FILE_APPEND);
}

// Acción: Comandos Artisan
if (isset($_POST['artisan'])) {
    Artisan::call($_POST['artisan']);
    $output = Artisan::output();
}

// Acción: Generar Ecosistema (AJAX)
if (isset($_POST['generate_model'])) {
    $name = preg_replace('/[^a-zA-Z]/', '', $_POST['generate_model']);
    $cmd = 'cd '.escapeshellarg($projectRoot).' && php artisan make:model '.$name.' -mfs 2>&1';
    echo shell_exec($cmd);
    exit;
}

// 3. INTROSPECCIÓN PARA EL DASHBOARD
$tablesInfo = [];
try {
    $rawTables = array_map('current', DB::select('SHOW TABLES'));
    foreach ($rawTables as $t) {
        $tablesInfo[] = ['name' => $t, 'count' => DB::table($t)->count()];
    }
} catch (Exception $e) {
    $tablesInfo = [];
}

$routes = [];
try {
    Artisan::call('route:list', ['--json' => true]);
    $routes = json_decode(Artisan::output(), true) ?? [];
} catch (Exception $e) {
    $routes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Project Lab v5</title>
    <style>
        :root { --bg: #020617; --card: #0f172a; --border: #1e293b; --text: #f1f5f9; --muted: #94a3b8; --accent: #3b82f6; --warning: #f59e0b; --danger: #ef4444; }
        body { margin: 0; padding: 20px; background: var(--bg); color: var(--text); font-family: 'Inter', system-ui, sans-serif; }
        .project-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; background: #10b981; box-shadow: 0 0 8px #10b981; }
        .dashboard-grid { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
        .sidebar { display: flex; flex-direction: column; gap: 20px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
        textarea { width: 100%; height: 300px; background: #020617; color: #10b981; border: 1px solid var(--border); border-radius: 8px; padding: 12px; font-family: monospace; }
        input { background: #1e293b; color: white; border: 1px solid var(--border); padding: 8px; border-radius: 6px; width: 100%; box-sizing: border-box; }
        button { background: var(--accent); color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; }
        .tab-btn { width: 100%; text-align: left; background: transparent; margin-bottom: 5px; color: var(--text); border: 1px solid transparent; cursor: pointer; padding: 8px; }
        .tab-btn.active { background: #1e293b; border-color: var(--accent); border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid var(--border); }
        .table-item { display: flex; justify-content: space-between; align-items: center; background: #1e293b; padding: 8px; border-radius: 6px; margin-bottom: 8px; }
    </style>
</head>
<body>

<header class="project-header">
    <div><h1 style="margin:0;">🧪 Project Lab <small style="color:var(--muted); font-size:12px;">v5.0</small></h1></div>
    <div style="font-size: 13px; color: var(--muted);">
        <span class="dot"></span> DB: <?= config('database.connections.mysql.database') ?> | PHP: <?= phpversion() ?>
    </div>
</header>

<div class="dashboard-grid">
    <aside class="sidebar">
        <div class="card">
            <h4 style="margin:0 0 10px 0;">💻 Menú</h4>
            <button class="tab-btn active" onclick="showTab('tinker')">Editor Tinker</button>
            <button class="tab-btn" onclick="showTab('database')">Base de Datos</button>
            <button class="tab-btn" onclick="showTab('routes')">Rutas</button>
        </div>
        <div class="card">
            <h4 style="margin:0 0 10px 0;">🚀 Generador</h4>
            <input type="text" id="modelName" placeholder="Modelo (Singular)">
            <button onclick="runGenerator()" class="warning" style="width:100%; margin-top:8px; background:var(--warning); color:#000;">Crear -mfs</button>
            <div id="genStatus" style="font-size:11px; margin-top:5px; color:var(--accent);"></div>
        </div>
    </aside>

    <main>
        <div id="tab-tinker" class="tab-content">
            <form method="POST">
                <div class="card">
                    <textarea name="code" id="code"><?= htmlspecialchars($code) ?></textarea>
                    <button type="submit" name="run" style="width:100%; margin-top:15px; font-size:14px;">EJECUTAR TINKER</button>
                </div>
            </form>
            <?php if ($output) { ?>
                <div class="card" style="margin-top:20px; background:#000; overflow-x: auto;">
                    <pre style="color:#10b981; font-size:12px;"><?= htmlspecialchars($output) ?></pre>
                </div>
            <?php } ?>
        </div>

        <div id="tab-database" class="tab-content" style="display:none;">
            <div class="card">
                <h3>Tablas</h3>
                <?php foreach ($tablesInfo as $t) { ?>
                    <div class="table-item">
                        <span><code><?= $t['name'] ?></code> (<?= $t['count'] ?>)</span>
                        <button onclick="insertCode('DB::table(\'<?= $t['name'] ?>\')->limit(10)->get();')">Query</button>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div id="tab-routes" class="tab-content" style="display:none;">
            <div class="card">
                <table>
                    <thead><tr><th>Met.</th><th>URI</th></tr></thead>
                    <tbody>
                        <?php foreach ($routes as $r) { ?>
                            <tr><td><?= $r['method'] ?></td><td><code><?= $r['uri'] ?></code></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    function showTab(n) {
        document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
        document.getElementById('tab-' + n).style.display = 'block';
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }
    function insertCode(c) {
        document.getElementById('code').value = c;
        showTab('tinker');
    }
    function runGenerator() {
        let name = document.getElementById('modelName').value;
        if(!name) return;
        document.getElementById('genStatus').innerText = 'Generando...';
        let fd = new FormData(); fd.append('generate_model', name);
        fetch(window.location.href, { method:'POST', body:fd }).then(r => r.text()).then(d => {
            alert(d); location.reload();
        });
    }
</script>
</body>
</html>
