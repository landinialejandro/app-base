<?php

// FILE: tools/dash-lab/index.php | V6

/**
 * PROJECT LAB V6 - Dashboard de Arquitectura y Control Mejorado
 * Compatible con Laravel 12.x
 * Mejoras: Seguridad, Rendimiento, UX y Monitoreo
 */

// 1. CARGA DE ENTORNO (Subimos 2 niveles desde tools/dash-lab/ a la raíz)
$projectRoot = dirname(__DIR__, 2);
require $projectRoot.'/vendor/autoload.php';

$app = require_once $projectRoot.'/bootstrap/app.php';

// Inicializar sesión para CSRF
session_start();

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
    http_response_code(403);
    exit('Acceso denegado: Project Lab solo local.');
}

// Clase Helper para operaciones comunes
class ProjectLabHelper
{
    private static $logFile;

    private static $projectRoot;

    public static function init($root)
    {
        self::$projectRoot = $root;
        self::$logFile = $root.'/documentos/log/project-lab.log';
        self::ensureLogDir();
    }

    private static function ensureLogDir()
    {
        $dir = dirname(self::$logFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    public static function log($type, $message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents(
            self::$logFile,
            "[{$timestamp}] {$type}: {$message}\n",
            FILE_APPEND
        );
    }

    public static function executeSecure($command, $timeout = 30)
    {
        $startTime = microtime(true);
        $output = shell_exec($command.' 2>&1');
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        self::log('EXEC', "Tiempo: {$executionTime}ms | Command: {$command}");

        return $output;
    }

    public static function getCache($key, $ttl = 300)
    {
        $cacheFile = self::$projectRoot.'/storage/framework/cache/projectlab_'.$key.'.cache';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        return null;
    }

    public static function setCache($key, $data)
    {
        $cacheFile = self::$projectRoot.'/storage/framework/cache/projectlab_'.$key.'.cache';
        $dir = dirname($cacheFile);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($cacheFile, json_encode($data));
    }
}

// Inicializar Helper
ProjectLabHelper::init($projectRoot);

// Protección CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (! hash_equals($csrfToken, $postToken)) {
        http_response_code(419);
        ProjectLabHelper::log('SECURITY', 'CSRF token mismatch');
        exit('Error de seguridad: Token CSRF inválido');
    }
}

// Generar o renovar token CSRF
if (! isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate Limiting
$rateLimitFile = sys_get_temp_dir().'/projectlab_ratelimit_'.md5($_SERVER['REMOTE_ADDR'] ?? 'local').'.json';
$rateData = json_decode(@file_get_contents($rateLimitFile), true) ?? ['count' => 0, 'reset' => time() + 3600];

if (time() > $rateData['reset']) {
    $rateData = ['count' => 0, 'reset' => time() + 3600];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rateData['count']++;
    if ($rateData['count'] > 50) {
        http_response_code(429);
        ProjectLabHelper::log('SECURITY', 'Rate limit exceeded');
        exit('Demasiadas solicitudes. Espera una hora.');
    }
}
file_put_contents($rateLimitFile, json_encode($rateData));

$csrfToken = $_SESSION['csrf_token'];

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
            // Guardamos el resultado de tu código
            \$result = (function() { 
                return {$code}; 
            })(); 
            
            // Lo imprimimos para que salga en el Dashboard
            if (isset(\$result)) {
                echo \"--- RESULTADO ---\n\";
                print_r(\$result);
            }
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
    $command = 'cd '.escapeshellarg($projectRoot).' && php artisan tinker --execute='.escapeshellarg($wrappedCode);
    $output = ProjectLabHelper::executeSecure($command);
    file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] TINKER EXEC:\n$output\n\n", FILE_APPEND);

    // Guardar en historial
    $historyFile = $projectRoot.'/storage/logs/tinker_history.json';
    $history = json_decode(@file_get_contents($historyFile), true) ?? [];
    array_unshift($history, [
        'code' => $code,
        'timestamp' => date('Y-m-d H:i:s'),
        'success' => strpos($output, 'ERROR:') === false,
        'preview' => substr($code, 0, 50).(strlen($code) > 50 ? '...' : ''),
    ]);
    $history = array_slice($history, 0, 15);
    file_put_contents($historyFile, json_encode($history));
}

// Acción: Comandos Artisan
if (isset($_POST['artisan'])) {
    $cmd = $_POST['artisan'];

    // Validar comandos peligrosos
    $dangerousCommands = ['migrate:fresh', 'migrate:reset', 'db:wipe'];
    $isDangerous = false;
    foreach ($dangerousCommands as $dc) {
        if (strpos($cmd, $dc) !== false) {
            $isDangerous = true;
            break;
        }
    }

    if ($isDangerous) {
        ProjectLabHelper::log('SECURITY', "Comando peligroso ejecutado: {$cmd}");
    }

    $output = ProjectLabHelper::executeSecure('cd '.escapeshellarg($projectRoot).' && php artisan '.$cmd);
    file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] ARTISAN: $cmd\n$output\n\n", FILE_APPEND);

    // Limpiar caché de introspección
    ProjectLabHelper::setCache('tables', null);
    ProjectLabHelper::setCache('routes', null);
}

// Acción: Generar Ecosistema (AJAX)
if (isset($_POST['generate_model'])) {
    $name = preg_replace('/[^a-zA-Z]/', '', $_POST['generate_model']);
    if (empty($name)) {
        http_response_code(400);
        echo 'Error: Nombre de modelo inválido';
        exit;
    }

    $cmd = 'cd '.escapeshellarg($projectRoot).' && php artisan make:model '.escapeshellarg($name).' -mfs';
    echo ProjectLabHelper::executeSecure($cmd);
    ProjectLabHelper::log('GEN', "Modelo creado: {$name}");
    exit;
}

// Acción: Describir tabla (AJAX)
if (isset($_POST['describe_table'])) {
    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['describe_table']);
    try {
        $columns = DB::select("DESCRIBE {$tableName}");
        echo "<table style='width:100%; font-size:11px;'>";
        echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>';
        foreach ($columns as $col) {
            $null = $col->Null === 'YES' ? '✓' : '✗';
            echo "<tr><td>{$col->Field}</td><td>{$col->Type}</td><td>{$null}</td><td>{$col->Key}</td></tr>";
        }
        echo '</table>';
    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage();
    }
    exit;
}

// Acción: Tail de logs (AJAX)
if (isset($_POST['tail_logs'])) {
    $logFile = $projectRoot.'/storage/logs/laravel.log';
    if (file_exists($logFile)) {
        $lines = isset($_POST['lines']) ? intval($_POST['lines']) : 50;
        $output = shell_exec("tail -n {$lines} ".escapeshellarg($logFile).' 2>&1');
        echo "<pre style='background:#000; color:#10b981; padding:15px; max-height:400px; overflow-y:auto; font-size:11px;'>";
        echo htmlspecialchars($output ?: 'Log vacío');
        echo '</pre>';
    } else {
        echo 'No se encontró el archivo de log.';
    }
    exit;
}

// 3. INTROSPECCIÓN PARA EL DASHBOARD (con caché)
$tablesInfo = ProjectLabHelper::getCache('tables', 300);
if ($tablesInfo === null) {
    $tablesInfo = [];
    try {
        $rawTables = array_map('current', DB::select('SHOW TABLES'));
        foreach ($rawTables as $t) {
            $tablesInfo[] = [
                'name' => $t,
                'count' => DB::table($t)->count(),
                'size' => DB::select('SELECT 
                    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                    [config('database.connections.mysql.database'), $t]
                )[0]->size ?? 0,
            ];
        }
        ProjectLabHelper::setCache('tables', $tablesInfo);
    } catch (Exception $e) {
        $tablesInfo = [];
    }
}

$routes = ProjectLabHelper::getCache('routes', 600);
if ($routes === null) {
    $routes = [];
    try {
        Artisan::call('route:list', ['--json' => true]);
        $routes = json_decode(Artisan::output(), true) ?? [];
        ProjectLabHelper::setCache('routes', $routes);
    } catch (Exception $e) {
        $routes = [];
    }
}

// Acción: Ejecutar Scripts Personalizados (.sh)
if (isset($_POST['run_script'])) {
    $script = $_POST['run_script'];
    // Validación mejorada con hash
    $allowedScripts = [
        'docs.sh' => $projectRoot.'/docs.sh',
        'codigos.sh' => $projectRoot.'/codigos.sh',
    ];

    if (array_key_exists($script, $allowedScripts)) {
        $path = $allowedScripts[$script];

        if (file_exists($path)) {
            // Verificar integridad del archivo
            $currentHash = md5_file($path);
            $storedHash = ProjectLabHelper::getCache('script_'.$script, 86400);

            if ($storedHash && $currentHash !== $storedHash) {
                ProjectLabHelper::log('SECURITY', "Script modificado: {$script}");
            }

            ProjectLabHelper::setCache('script_'.$script, $currentHash);

            // Ejecutar con permisos seguros
            $cmd = 'cd '.escapeshellarg($projectRoot).' && sh '.escapeshellarg($script);
            $output = ProjectLabHelper::executeSecure($cmd);
            echo "--- SALIDA DE $script ---\n".$output;
        } else {
            echo "Error: El archivo $script no existe en la raíz.";
        }
    }
    exit;
}

// Datos de monitoreo
$memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
$peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
$diskFree = round(disk_free_space($projectRoot) / 1024 / 1024 / 1024, 2);
$diskTotal = round(disk_total_space($projectRoot) / 1024 / 1024 / 1024, 2);

// Cargar historial de Tinker
$historyFile = $projectRoot.'/storage/logs/tinker_history.json';
$tinkerHistory = json_decode(@file_get_contents($historyFile), true) ?? [];

// Datos adicionales para el Monitor
$laravelInfo = [
    'version' => app()->version(),
    'environment' => config('app.env'),
    'debug' => config('app.debug') ? 'Activado' : 'Desactivado',
    'url' => config('app.url'),
    'timezone' => config('app.timezone'),
    'locale' => config('app.locale'),
];

// Paquetes instalados (composer)
$composerLock = $projectRoot.'/composer.lock';
$packages = [];
if (file_exists($composerLock)) {
    $lockData = json_decode(file_get_contents($composerLock), true);
    $packages = $lockData['packages'] ?? [];

    // Organizar por tipo
    $laravelPackages = array_filter($packages, function ($p) {
        return strpos($p['name'] ?? '', 'laravel/') !== false;
    });

    $vendorCounts = [];
    foreach ($packages as $pkg) {
        $vendor = explode('/', $pkg['name'])[0];
        $vendorCounts[$vendor] = ($vendorCounts[$vendor] ?? 0) + 1;
    }
    arsort($vendorCounts);

    $totalPackages = count($packages);
    $totalVendors = count($vendorCounts);
}

// Configuración de base de datos
$dbInfo = [
    'driver' => config('database.default'),
    'host' => config('database.connections.'.config('database.default').'.host'),
    'database' => config('database.connections.'.config('database.default').'.database'),
    'charset' => config('database.connections.'.config('database.default').'.charset'),
];

// Drivers y servicios disponibles
$availableDrivers = [
    'PDO Drivers' => extension_loaded('pdo') ? implode(', ', PDO::getAvailableDrivers()) : 'No disponible',
    'Redis' => extension_loaded('redis') ? '✅ Disponible' : '❌ No instalado',
    'Memcached' => extension_loaded('memcached') ? '✅ Disponible' : '❌ No instalado',
    'GD/Imagick' => extension_loaded('gd') ? 'GD ✅' : (extension_loaded('imagick') ? 'Imagick ✅' : '❌ Ninguno'),
];

// Cache y Session drivers
$cacheDrivers = [
    'Cache Default' => config('cache.default'),
    'Session' => config('session.driver'),
    'Queue' => config('queue.default'),
    'Filesystem' => config('filesystems.default'),
];

// Tamaño de carpetas importantes
$folderSizes = [];
$foldersToCheck = ['storage', 'vendor', 'node_modules', 'public'];
foreach ($foldersToCheck as $folder) {
    $path = $projectRoot.'/'.$folder;
    if (is_dir($path)) {
        $folderSizes[$folder] = round(folderSize($path) / 1024 / 1024, 2); // MB
    }
}

function folderSize($dir)
{
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : folderSize($each);
    }

    return $size;
}

// Paquetes destacados
$featuredPackages = [
    'spatie/laravel-permission' => 'Gestión de roles y permisos',
    'barryvdh/laravel-debugbar' => 'Debugbar',
    'maatwebsite/excel' => 'Excel',
    'laravel/sanctum' => 'API Tokens',
    'laravel/horizon' => 'Queue Monitor',
    'livewire/livewire' => 'Full-stack components',
    'inertiajs/inertia-laravel' => 'SPA adapter',
];

$installedFeatured = [];
foreach ($featuredPackages as $package => $description) {
    foreach ($packages as $pkg) {
        if ($pkg['name'] === $package) {
            $installedFeatured[$package] = [
                'version' => $pkg['version'] ?? 'N/A',
                'description' => $description,
            ];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Lab v6 - Mejorado</title>
    <style>
        :root { --bg: #020617; --card: #0f172a; --border: #1e293b; --text: #f1f5f9; --muted: #94a3b8; --accent: #3b82f6; --warning: #f59e0b; --danger: #ef4444; --success: #10b981; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 20px; background: var(--bg); color: var(--text); font-family: 'Inter', system-ui, -apple-system, sans-serif; line-height: 1.5; }
        .project-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; background: var(--success); box-shadow: 0 0 8px var(--success); animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        .dashboard-grid { display: grid; grid-template-columns: 300px 1fr; gap: 20px; }
        .sidebar { display: flex; flex-direction: column; gap: 20px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
        textarea { 
            width: 100%; max-width: 100%; height: 350px; min-height: 200px; 
            background: #020617; color: var(--success); border: 1px solid var(--border); 
            border-radius: 8px; padding: 12px; font-family: 'Fira Code', 'Courier New', monospace; 
            box-sizing: border-box; resize: vertical; line-height: 1.5; font-size: 13px;
        }
        textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1); }
        input { background: #1e293b; color: white; border: 1px solid var(--border); padding: 8px 12px; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 13px; }
        input:focus { outline: none; border-color: var(--accent); }
        button { 
            background: var(--accent); color: white; border: none; padding: 10px 16px; 
            border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; 
            transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.5px;
        }
        button:hover { opacity: 0.9; transform: translateY(-1px); }
        button:active { transform: translateY(0); }
        button.secondary { background: #334155; }
        button.warning { background: var(--warning); color: #000; }
        button.danger { background: var(--danger); }
        .tab-btn { 
            width: 100%; text-align: left; background: transparent; margin-bottom: 5px; 
            color: var(--text); border: 1px solid transparent; cursor: pointer; padding: 10px; 
            font-size: 13px; text-transform: none; letter-spacing: 0;
        }
        .tab-btn.active { background: #1e293b; border-color: var(--accent); border-radius: 6px; }
        .tab-btn:hover:not(.active) { background: #1e293b; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid var(--border); }
        th { font-weight: 600; color: var(--muted); text-transform: uppercase; font-size: 11px; }
        .table-item { 
            display: flex; justify-content: space-between; align-items: center; 
            background: #1e293b; padding: 12px; border-radius: 6px; margin-bottom: 8px; 
            transition: background 0.2s;
        }
        .table-item:hover { background: #273548; }
        .output-card {
            margin-top: 20px; background: #000 !important; border: 1px solid #334155;
            border-radius: 12px; width: 100%; max-width: 100%; box-sizing: border-box; 
            overflow: hidden;
        }
        .output-card pre {
            margin: 0; padding: 15px; color: var(--success); font-size: 12px;
            font-family: 'Fira Code', monospace; display: block; width: 100%;
            overflow-x: auto; white-space: pre; box-sizing: border-box;
        }
        .output-card pre::-webkit-scrollbar { height: 8px; }
        .output-card pre::-webkit-scrollbar-track { background: #020617; }
        .output-card pre::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 4px; }
        main { min-width: 0; overflow: hidden; }
        
        /* Estados y badges */
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 12px; 
            font-size: 10px; font-weight: 600; text-transform: uppercase;
        }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
        
        /* Snippets bar */
        .snippets-bar { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 8px; }
        .snippet-chip {
            background: #1e293b; color: var(--success); border: 1px solid #334155;
            border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 11px;
            font-family: 'Fira Code', monospace; transition: all 0.2s;
        }
        .snippet-chip:hover { background: #273548; border-color: var(--accent); }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .sidebar { order: -1; }
            textarea { height: 250px; }
        }
        @media (max-width: 640px) {
            body { padding: 10px; }
            .project-header { flex-direction: column; align-items: flex-start; }
            textarea { height: 200px; font-size: 11px; }
            button { padding: 8px 12px; font-size: 11px; }
        }
        /* Mejoras visuales para badges y métricas */
        .badge {
            display: inline-block; 
            padding: 2px 8px; 
            border-radius: 12px; 
            font-size: 10px; 
            font-weight: 600; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success { 
            background: rgba(16, 185, 129, 0.2); 
            color: var(--success); 
        }

        .badge-danger { 
            background: rgba(239, 68, 68, 0.2); 
            color: var(--danger); 
        }

        .badge-warning { 
            background: rgba(245, 158, 11, 0.2); 
            color: var(--warning); 
        }

        /* Animaciones sutiles */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.3s ease-out;
        }

        /* Responsive para el grid del monitor */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            [style*="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr))"] {
                grid-template-columns: 1fr !important;
            }
        }

        /* Estilos para las métricas rápidas */
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>

<header class="project-header">
    <div>
        <h1 style="margin:0;">🧪 Project Lab <small style="color:var(--muted); font-size:12px;">v6.0 Mejorado</small></h1>
        <div style="font-size: 11px; color: var(--muted); margin-top: 5px;">
            Rate Limit: <?= $rateData['count'] ?>/50 • Reset: <?= date('H:i', $rateData['reset']) ?>
        </div>
    </div>
    <div style="font-size: 13px; color: var(--muted);">
        <span class="dot"></span> DB: <?= config('database.connections.mysql.database') ?> | PHP: <?= phpversion() ?> | RAM: <?= $memoryUsage ?>MB
    </div>
</header>

<div class="dashboard-grid">
    <aside class="sidebar">
        <!-- Menú de Navegación -->
        <div class="card">
            <h4 style="margin:0 0 15px 0;">💻 Menú</h4>
            <button class="tab-btn active" onclick="showTab('tinker')">🧪 Editor Tinker</button>
            <button class="tab-btn" onclick="showTab('database')">🗄️ Base de Datos</button>
            <button class="tab-btn" onclick="showTab('routes')">🔗 Rutas (<?= count($routes) ?>)</button>
            <button class="tab-btn" onclick="showTab('monitor')">📊 Monitor</button>
        </div>

        <!-- Historial de Tinker -->
        <?php if (! empty($tinkerHistory)) { ?>
        <div class="card">
            <h4 style="margin:0 0 10px 0;">📝 Historial Tinker</h4>
            <div style="max-height: 200px; overflow-y: auto;">
                <?php foreach ($tinkerHistory as $item) { ?>
                <div style="font-size:11px; margin-bottom:8px; cursor:pointer; padding:5px; border-radius:4px; background:#1e293b;"
                     onclick="document.getElementById('code').value=<?= htmlspecialchars(json_encode($item['code'])) ?>; showTab('tinker');"
                     title="<?= htmlspecialchars($item['code']) ?>">
                    <?= $item['success'] ? '✅' : '❌' ?> 
                    <?= htmlspecialchars($item['preview']) ?><br>
                    <small style="color:#94a3b8;"><?= $item['timestamp'] ?></small>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <!-- Scripts Personalizados -->
        <div class="card">
            <h4 style="margin:0 0 10px 0;">📜 Scripts Personalizados</h4>
            <div class="btn-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                <button type="button" onclick="runCustomScript('docs.sh')" class="secondary">📄 Docs.sh</button>
                <button type="button" onclick="runCustomScript('codigos.sh')" class="secondary">💻 Codigos.sh</button>
            </div>
            <div id="scriptStatus" style="font-size:11px; margin-top:8px; color:var(--muted);"></div>
        </div>

        <!-- Aceleradores Artisan -->
        <div class="card">
            <h4 style="margin:0 0 10px 0;">⚡ Aceleradores Artisan</h4>
            <form method="POST" id="artisanForm" style="display:grid; gap:8px;">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button name="artisan" value="optimize:clear" class="secondary">🧹 Limpiar Todo</button>
                <button name="artisan" value="migrate" class="secondary">🗂️ Migraciones</button>
                <button name="artisan" value="db:seed" class="secondary">🌱 Seeders</button>
                <hr style="border:0; border-top:1px solid var(--border); margin:5px 0;">
                <button name="artisan" value="migrate:fresh --seed" 
                        class="warning" 
                        data-danger="true"
                        data-confirm="⚠️ ¡ALERTA! Esto ELIMINARÁ TODA la base de datos.\n\nEscribe 'BORRAR' para confirmar:">
                    🔥 Fresh + Seed (Reset Total)
                </button>
                <button type="button" onclick="tailLogs()" class="secondary">📋 Ver Últimos Logs</button>
            </form>
        </div>

        <!-- Generador de Modelos -->
        <div class="card">
            <h4 style="margin:0 0 10px 0;">🚀 Generador Rápido</h4>
            <input type="text" id="modelName" placeholder="Nombre del Modelo (ej: Product)" style="margin-bottom:8px;">
            <button onclick="runGenerator()" class="warning" style="width:100%; background:var(--warning); color:#000;">
                ⚡ Crear Modelo (-mfs)
            </button>
            <div id="genStatus" style="font-size:11px; margin-top:8px; color:var(--accent); min-height: 20px;"></div>
        </div>
    </aside>

    <main>
        <!-- Pestaña Editor Tinker -->
        <div id="tab-tinker" class="tab-content">
            <form method="POST" id="tinkerForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin:0;">Código Tinker</h4>
                        <span style="font-size: 11px; color: var(--muted);">
                            Ctrl+Enter para ejecutar • Ctrl+L para limpiar
                        </span>
                    </div>
                    <textarea name="code" id="code" placeholder="// Escribe tu código PHP aquí...&#10;// Ejemplo: User::first()&#10;// Ejemplo: DB::table('users')->get()"><?= htmlspecialchars($code) ?></textarea>
                    
                    <!-- Snippets rápidos -->
                    <div class="snippets-bar">
                        <span style="color: var(--muted); font-size: 11px; margin-right: 5px;">Quick:</span>
                        <span class="snippet-chip" onclick="insertSnippet('User::all()')">User::all()</span>
                        <span class="snippet-chip" onclick="insertSnippet('DB::table(\'users\')->get()')">DB::table()</span>
                        <span class="snippet-chip" onclick="insertSnippet('App\\Models\\')">App\Models\</span>
                        <span class="snippet-chip" onclick="insertSnippet('config(\'app.name\')')">config()</span>
                        <span class="snippet-chip" onclick="insertSnippet('cache(\'key\')')">cache()</span>
                        <span class="snippet-chip" onclick="insertSnippet('now()')">now()</span>
                    </div>
                    
                    <div style="margin-top:15px; display:flex; gap:10px;">
                        <button type="submit" name="run" style="flex:2; font-size:14px;">▶ EJECUTAR TINKER</button>
                        <button type="button" onclick="copyOutput()" class="secondary" style="flex:1;">📋 Copiar</button>
                        <button type="button" onclick="exportOutput()" class="secondary" style="flex:1;">💾 Exportar</button>
                        <button type="button" onclick="clearTinker()" class="danger" style="flex:1;">🗑️ Limpiar</button>
                    </div>
                </div>
            </form>
            <?php if ($output) { ?>
                <div class="card output-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span style="color:var(--muted); font-size:11px;">📤 Resultado</span>
                        <button onclick="copyOutput()" class="secondary" style="font-size:10px; padding:4px 8px;">Copiar</button>
                    </div>
                    <pre><?= htmlspecialchars($output) ?></pre>
                </div>
            <?php } ?>
        </div>

        <!-- Pestaña Base de Datos -->
        <div id="tab-database" class="tab-content" style="display:none;">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3 style="margin:0;">🗄️ Tablas de la Base de Datos</h3>
                    <span style="color:var(--muted); font-size:12px;"><?= count($tablesInfo) ?> tablas • Click para ver estructura</span>
                </div>
                <?php if (empty($tablesInfo)) { ?>
                    <div style="text-align:center; padding:40px; color:var(--muted);">
                        <p>No se encontraron tablas o no hay conexión a la base de datos</p>
                    </div>
                <?php } else { ?>
                    <?php foreach ($tablesInfo as $t) { ?>
                        <div class="table-item" style="cursor:pointer;" onclick="loadTableDetails('<?= $t['name'] ?>', this)">
                            <div>
                                <strong><code style="color:var(--accent);"><?= $t['name'] ?></code></strong>
                                <div style="font-size:11px; color:var(--muted); margin-top:4px;">
                                    <?= number_format($t['count']) ?> registros • <?= $t['size'] ?> MB
                                </div>
                            </div>
                            <div style="display:flex; gap:5px;">
                                <button onclick="event.stopPropagation(); insertCode('DB::table(\'<?= $t['name'] ?>\')->limit(10)->get();')" 
                                        class="secondary" style="font-size:10px; padding:4px 8px;">Query</button>
                                <button onclick="event.stopPropagation(); insertCode('DB::table(\'<?= $t['name'] ?>\')->count();')" 
                                        class="secondary" style="font-size:10px; padding:4px 8px;">Count</button>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <!-- Pestaña Rutas -->
        <div id="tab-routes" class="tab-content" style="display:none;">
            <div class="card">
                <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                    <input type="text" id="routeSearch" placeholder="🔍 Filtrar rutas..." onkeyup="filterRoutes()" style="flex:2; min-width:200px;">
                    <button type="button" onclick="copyAllRoutes()" class="secondary">📋 Copiar Visibles</button>
                    <button type="button" onclick="refreshRoutes()" class="warning">🔄 Actualizar</button>
                </div>
                <div style="max-height: 600px; overflow-y: auto;">
                    <table id="routeTable">
                        <thead>
                            <tr>
                                <th style="width:60px;">Método</th>
                                <th>URI</th>
                                <th>Nombre</th>
                                <th style="width:100px;">Middleware</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($routes)) { ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:40px; color:var(--muted);">
                                        No se encontraron rutas
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($routes as $r) { ?>
                                    <tr class="route-row">
                                        <td>
                                            <span class="badge badge-<?= in_array($r['method'], ['GET', 'HEAD']) ? 'success' : 'danger' ?>">
                                                <?= $r['method'] ?>
                                            </span>
                                        </td>
                                        <td><code style="color:var(--success);"><?= htmlspecialchars($r['uri']) ?></code></td>
                                        <td style="color:var(--muted); font-size:11px;"><?= $r['name'] ?? '-' ?></td>
                                        <td style="font-size:10px; color:var(--muted);">
                                            <?= is_array($r['middleware'] ?? null) ? implode(', ', $r['middleware']) : ($r['middleware'] ?? '-') ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pestaña Monitor -->
        <div id="tab-monitor" class="tab-content" style="display:none;">
            <div class="card" style="margin-bottom: 20px;">
                <h3 style="margin:0 0 20px 0;">📊 Monitor del Sistema</h3>
                
                <!-- Métricas rápidas -->
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px; margin-bottom: 20px;">
                    <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 3px solid var(--accent);">
                        <div style="font-size: 11px; color: var(--muted);">PHP</div>
                        <div style="font-size: 20px; font-weight: bold;"><?= phpversion() ?></div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 3px solid var(--success);">
                        <div style="font-size: 11px; color: var(--muted);">Laravel</div>
                        <div style="font-size: 20px; font-weight: bold;"><?= $laravelInfo['version'] ?></div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 3px solid var(--warning);">
                        <div style="font-size: 11px; color: var(--muted);">Paquetes</div>
                        <div style="font-size: 20px; font-weight: bold;"><?= $totalPackages ?? 0 ?></div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 3px solid #8b5cf6;">
                        <div style="font-size: 11px; color: var(--muted);">Vendors</div>
                        <div style="font-size: 20px; font-weight: bold;"><?= $totalVendors ?? 0 ?></div>
                    </div>
                </div>
                
                <!-- Grid principal -->
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap:20px;">
                    
                    <!-- Columna 1: Laravel & Entorno -->
                    <div>
                        <!-- Información de Laravel -->
                        <div class="card" style="background:#1e293b; margin-bottom: 20px;">
                            <h4 style="margin:0 0 15px 0; color:var(--accent); display:flex; align-items:center; gap:8px;">
                                <span>🚀 Laravel</span>
                                <span class="badge badge-success"><?= $laravelInfo['version'] ?></span>
                            </h4>
                            <div class="table-item"><span>Entorno</span><span><?= $laravelInfo['environment'] ?></span></div>
                            <div class="table-item">
                                <span>Debug Mode</span>
                                <span class="badge <?= $laravelInfo['debug'] === 'Activado' ? 'badge-danger' : 'badge-success' ?>">
                                    <?= $laravelInfo['debug'] ?>
                                </span>
                            </div>
                            <div class="table-item"><span>URL</span><span><?= $laravelInfo['url'] ?></span></div>
                            <div class="table-item"><span>Timezone</span><span><?= $laravelInfo['timezone'] ?></span></div>
                            <div class="table-item"><span>Locale</span><span><?= $laravelInfo['locale'] ?></span></div>
                        </div>
                        
                        <!-- Configuración DB -->
                        <div class="card" style="background:#1e293b; margin-bottom: 20px;">
                            <h4 style="margin:0 0 15px 0; color:var(--success);">🗄️ Base de Datos</h4>
                            <div class="table-item"><span>Driver</span><span><?= $dbInfo['driver'] ?></span></div>
                            <div class="table-item"><span>Host</span><span><?= $dbInfo['host'] ?></span></div>
                            <div class="table-item"><span>Database</span><span><?= $dbInfo['database'] ?></span></div>
                            <div class="table-item"><span>Charset</span><span><?= $dbInfo['charset'] ?></span></div>
                        </div>
                        
                        <!-- Cache & Session -->
                        <div class="card" style="background:#1e293b;">
                            <h4 style="margin:0 0 15px 0; color:var(--warning);">⚡ Drivers Activos</h4>
                            <?php foreach ($cacheDrivers as $name => $driver) { ?>
                            <div class="table-item">
                                <span><?= $name ?></span>
                                <span style="color:var(--accent);"><?= $driver ?></span>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <!-- Columna 2: Sistema & Extensiones -->
                    <div>
                        <!-- Recursos del Sistema -->
                        <div class="card" style="background:#1e293b; margin-bottom: 20px;">
                            <h4 style="margin:0 0 15px 0; color:var(--warning);">💻 Recursos</h4>
                            <div class="table-item">
                                <span>Memoria Actual</span>
                                <span><?= $memoryUsage ?> MB</span>
                            </div>
                            <div class="table-item">
                                <span>Memoria Pico</span>
                                <span><?= $peakMemory ?> MB</span>
                            </div>
                            <div class="table-item">
                                <span>Límite PHP</span>
                                <span><?= ini_get('memory_limit') ?></span>
                            </div>
                            <div class="table-item">
                                <span>Disco Libre</span>
                                <span><?= $diskFree ?> GB / <?= $diskTotal ?> GB</span>
                            </div>
                            
                            <!-- Barras de uso -->
                            <div style="margin-top: 15px;">
                                <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:5px;">
                                    <span>Disco Usado</span>
                                    <span><?= round((1 - $diskFree / $diskTotal) * 100) ?>%</span>
                                </div>
                                <div style="background:#020617; height: 8px; border-radius: 4px; overflow:hidden;">
                                    <div style="background: linear-gradient(90deg, var(--success), var(--warning), var(--danger)); 
                                                width: <?= round((1 - $diskFree / $diskTotal) * 100) ?>%; 
                                                height: 100%; transition: width 0.5s;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Extensiones PHP -->
                        <div class="card" style="background:#1e293b; margin-bottom: 20px;">
                            <h4 style="margin:0 0 15px 0; color:#8b5cf6;">🔌 Extensiones</h4>
                            <?php foreach ($availableDrivers as $name => $status) { ?>
                            <div class="table-item">
                                <span><?= $name ?></span>
                                <span style="font-size:11px;">
                                    <?= strpos($status, '✅') !== false
                                        ? '<span style="color:var(--success);">'.$status.'</span>'
                                        : '<span style="color:var(--danger);">'.$status.'</span>' ?>
                                </span>
                            </div>
                            <?php } ?>
                        </div>
                        
                        <!-- Tamaño de Carpetas -->
                        <?php if (! empty($folderSizes)) { ?>
                        <div class="card" style="background:#1e293b;">
                            <h4 style="margin:0 0 15px 0; color:#ec4899;">📦 Tamaños de Directorios</h4>
                            <?php foreach ($folderSizes as $folder => $size) { ?>
                            <div class="table-item">
                                <span><?= $folder ?></span>
                                <span><?= $size > 1024 ? round($size / 1024, 2).' GB' : $size.' MB' ?></span>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                
                <!-- Paquetes Destacados -->
                <?php if (! empty($installedFeatured)) { ?>
                <div class="card" style="background:#1e293b; margin-top: 20px;">
                    <h4 style="margin:0 0 15px 0; color:var(--accent);">⭐ Paquetes Destacados Instalados</h4>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:10px;">
                        <?php foreach ($installedFeatured as $package => $info) { ?>
                        <div class="card" style="background:#020617; padding:12px;">
                            <div style="font-weight:600; color:var(--success); margin-bottom:4px;"><?= $package ?></div>
                            <div style="font-size:11px; color:var(--muted); margin-bottom:4px;"><?= $info['description'] ?></div>
                            <span class="badge badge-success" style="font-size:10px;">v<?= $info['version'] ?></span>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
                
                <!-- Top Vendors -->
                <?php if (! empty($vendorCounts)) { ?>
                <div class="card" style="background:#1e293b; margin-top: 20px;">
                    <h4 style="margin:0 0 15px 0; color:var(--warning);">
                        🏢 Top Vendors 
                        <small style="color:var(--muted); font-weight:normal;">(<?= $totalVendors ?> vendors, <?= $totalPackages ?> paquetes)</small>
                    </h4>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:8px;">
                        <?php
                        $topVendors = array_slice($vendorCounts, 0, 20);
                    foreach ($topVendors as $vendor => $count) {
                        ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; 
                                    background:#020617; padding:8px 12px; border-radius:6px;">
                            <span style="font-weight:500;"><?= $vendor ?></span>
                            <span class="badge" style="background:rgba(59,130,246,0.2); color:var(--accent);">
                                <?= $count ?> <?= $count === 1 ? 'pkg' : 'pkgs' ?>
                            </span>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
                
                <!-- Botones de acción -->
                <div style="display:flex; gap:10px; margin-top: 20px;">
                    <button onclick="tailLogs(100)" class="secondary" style="flex:1;">
                        📋 Ver Últimos Logs
                    </button>
                    <button onclick="location.reload()" class="warning" style="flex:1;">
                        🔄 Actualizar Métricas
                    </button>
                </div>
                
                <!-- Visor de Logs -->
                <div id="logViewer" style="margin-top:20px;"></div>
            </div>
        </div>
    </main>
</div>

<script>
    // Configuración global
    const CSRF_TOKEN = '<?= $csrfToken ?>';
    
    // Atajos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + Enter para ejecutar
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            document.querySelector('#tinkerForm button[name="run"]')?.click();
        }
        
        // Ctrl/Cmd + L para limpiar
        if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
            e.preventDefault();
            clearTinker();
        }
        
        // Ctrl/Cmd + 1-4 para cambiar pestañas
        if ((e.ctrlKey || e.metaKey)) {
            const tabs = { '1': 'tinker', '2': 'database', '3': 'routes', '4': 'monitor' };
            if (tabs[e.key]) {
                e.preventDefault();
                showTab(tabs[e.key]);
            }
        }
    });

    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
        const targetTab = document.getElementById('tab-' + tabName);
        if (targetTab) targetTab.style.display = 'block';
        
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        const activeBtn = document.querySelector(`.tab-btn[onclick="showTab('${tabName}')"]`);
        if (activeBtn) activeBtn.classList.add('active');
    }
    
    function insertCode(c) {
        document.getElementById('code').value = c;
        showTab('tinker');
        document.getElementById('code').focus();
    }
    
    function insertSnippet(snippet) {
        const textarea = document.getElementById('code');
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);
        textarea.value = textBefore + snippet + textAfter;
        textarea.focus();
        textarea.selectionStart = cursorPos + snippet.length;
        textarea.selectionEnd = cursorPos + snippet.length;
    }
    
    function runGenerator() {
        let name = document.getElementById('modelName').value.trim();
        if(!name) {
            alert('Por favor, ingresa un nombre de modelo');
            return;
        }
        
        const genStatus = document.getElementById('genStatus');
        genStatus.innerHTML = '⏳ Generando modelo ' + name + '...';
        
        let fd = new FormData(); 
        fd.append('generate_model', name);
        fd.append('csrf_token', CSRF_TOKEN);
        
        fetch(window.location.href, { 
            method: 'POST', 
            body: fd 
        })
        .then(r => r.text())
        .then(d => {
            genStatus.innerHTML = '✅ ¡Modelo creado! Recargando...';
            alert('Resultado:\n\n' + d);
            setTimeout(() => location.reload(), 1000);
        })
        .catch(err => {
            genStatus.innerHTML = '❌ Error al generar';
            alert('Error: ' + err);
        });
    }
    
    function clearTinker() {
        document.getElementById('code').value = '';
        const outputCards = document.querySelectorAll('.output-card');
        outputCards.forEach(card => card.remove());
    }
    
    function copyAllRoutes() {
        const rows = document.querySelectorAll('#routeTable tbody tr.route-row');
        let textToCopy = "";
        const visibleRows = [];

        rows.forEach(row => {
            if (row.style.display !== "none") {
                const cols = row.querySelectorAll('td');
                const method = cols[0]?.innerText.trim() || '';
                const uri = cols[1]?.innerText.trim() || '';
                const name = cols[2]?.innerText.trim() || '';
                textToCopy += `${method} | ${uri} | ${name}\n`;
            }
        });

        if (!textToCopy) {
            alert("No hay rutas visibles para copiar");
            return;
        }

        copyToClipboard(textToCopy, 'Rutas copiadas al portapapeles');
    }

    function filterRoutes() {
        const input = document.getElementById('routeSearch');
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('#routeTable tbody tr.route-row');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
        
        // Actualizar contador
        const visibleCount = document.querySelectorAll('#routeTable tbody tr.route-row[style="display:;"]').length;
        const tabBtn = document.querySelector('.tab-btn[onclick="showTab(\'routes\')"]');
        if (tabBtn) {
            const total = rows.length;
            tabBtn.textContent = `🔗 Rutas (${visibleCount}/${total})`;
        }
    }
    
    function refreshRoutes() {
        const btn = event.target;
        btn.textContent = '⏳ Actualizando...';
        btn.disabled = true;
        
        let fd = new FormData();
        fd.append('csrf_token', CSRF_TOKEN);
        fd.append('refresh_routes', '1');
        
        fetch(window.location.href, { method: 'POST', body: fd })
            .then(() => location.reload())
            .catch(err => {
                alert('Error al actualizar: ' + err);
                btn.textContent = '🔄 Actualizar';
                btn.disabled = false;
            });
    }
    
    function copyOutput() {
        const output = document.querySelector('.output-card pre');
        if (!output) {
            alert('No hay salida para copiar');
            return;
        }

        copyToClipboard(output.innerText, 'Salida copiada al portapapeles', event?.target);
    }
    
    function exportOutput() {
        const output = document.querySelector('.output-card pre')?.innerText;
        if (!output) {
            alert('No hay salida para exportar');
            return;
        }
        
        const blob = new Blob([output], {type: 'text/plain'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `tinker-output-${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    function copyToClipboard(text, successMessage, button) {
        navigator.clipboard.writeText(text).then(() => {
            if (button) {
                const originalText = button.innerText;
                const originalBg = button.style.background;
                button.innerText = '✓ ¡Copiado!';
                button.style.background = '#10b981';
                
                setTimeout(() => {
                    button.innerText = originalText;
                    button.style.background = originalBg;
                }, 2000);
            } else {
                alert(successMessage);
            }
        }).catch(() => {
            // Fallback para navegadores antiguos
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert(successMessage);
        });
    }

    function runCustomScript(scriptName) {
        const status = document.getElementById('scriptStatus');
        status.innerHTML = '⏳ Ejecutando ' + scriptName + '...';

        let formData = new FormData();
        formData.append('run_script', scriptName);
        formData.append('csrf_token', CSRF_TOKEN);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Mostrar resultado en pestaña Tinker
            const tinkerTab = document.getElementById('tab-tinker');
            
            // Eliminar salida anterior si existe
            const oldOutput = tinkerTab.querySelector('.output-card');
            if (oldOutput) oldOutput.remove();
            
            // Crear nueva salida
            const outputCard = document.createElement('div');
            outputCard.className = 'card output-card';
            outputCard.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="color:var(--muted); font-size:11px;">📤 Salida de ${scriptName}</span>
                    <button onclick="copyOutput()" class="secondary" style="font-size:10px; padding:4px 8px;">Copiar</button>
                </div>
                <pre>${escapeHtml(data)}</pre>
            `;
            
            tinkerTab.appendChild(outputCard);
            showTab('tinker');
            status.innerHTML = '✅ ' + scriptName + ' finalizado.';
            
            // Scroll a la salida
            outputCard.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(err => {
            status.innerHTML = '❌ Error al ejecutar ' + scriptName;
            alert('Error al ejecutar el script: ' + err);
        });
    }
    
    function loadTableDetails(tableName, element) {
        // Verificar si ya está cargada
        if (element.querySelector('.table-details')) {
            const details = element.querySelector('.table-details');
            details.style.display = details.style.display === 'none' ? 'block' : 'none';
            return;
        }
        
        const detailsDiv = document.createElement('div');
        detailsDiv.className = 'table-details';
        detailsDiv.style.cssText = 'margin-top:8px; padding:8px; background:#020617; border-radius:6px; font-size:11px;';
        detailsDiv.innerHTML = '⏳ Cargando estructura...';
        element.appendChild(detailsDiv);
        
        let formData = new FormData();
        formData.append('describe_table', tableName);
        formData.append('csrf_token', CSRF_TOKEN);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            detailsDiv.innerHTML = data;
        })
        .catch(err => {
            detailsDiv.innerHTML = '❌ Error al cargar estructura';
        });
    }
    
    function tailLogs(lines = 50) {
        const logViewer = document.getElementById('logViewer');
        if (!logViewer) return;
        
        logViewer.innerHTML = '⏳ Cargando logs...';
        
        let formData = new FormData();
        formData.append('tail_logs', '1');
        formData.append('lines', lines);
        formData.append('csrf_token', CSRF_TOKEN);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            logViewer.innerHTML = data;
            showTab('monitor');
        })
        .catch(err => {
            logViewer.innerHTML = '❌ Error al cargar logs';
        });
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Confirmación para comandos peligrosos
    document.querySelectorAll('[data-danger="true"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const required = this.dataset.confirm;
            const userInput = prompt(required);
            if (userInput !== 'BORRAR') {
                e.preventDefault();
                e.stopPropagation();
                alert('❌ Acción cancelada. Debes escribir BORRAR para confirmar.');
            }
        });
    });
    
    // Función mejorada para mostrar logs
    function tailLogs(lines = 50) {
        const logViewer = document.getElementById('logViewer');
        if (!logViewer) return;
        
        // Mostrar indicador de carga con animación
        logViewer.innerHTML = `
            <div class="card" style="background:#1e293b; text-align:center; padding:40px;">
                <div style="font-size:24px; margin-bottom:10px;">⏳</div>
                <div style="color:var(--muted);">Cargando logs...</div>
            </div>
        `;
        logViewer.scrollIntoView({ behavior: 'smooth' });
        
        let formData = new FormData();
        formData.append('tail_logs', '1');
        formData.append('lines', lines);
        formData.append('csrf_token', CSRF_TOKEN);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            logViewer.innerHTML = `
                <div class="card" style="background:#1e293b; margin-top:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                        <h4 style="margin:0; color:var(--warning);">📝 Últimos ${lines} logs</h4>
                        <div>
                            <button onclick="tailLogs(50)" class="secondary" style="font-size:10px; padding:4px 8px;">50</button>
                            <button onclick="tailLogs(100)" class="secondary" style="font-size:10px; padding:4px 8px;">100</button>
                            <button onclick="tailLogs(200)" class="secondary" style="font-size:10px; padding:4px 8px;">200</button>
                        </div>
                    </div>
                    <div style="background:#000; border-radius:8px; padding:2px;">
                        ${data}
                    </div>
                </div>
            `;
            logViewer.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(err => {
            logViewer.innerHTML = `
                <div class="card" style="background:#1e293b; text-align:center; padding:40px;">
                    <div style="font-size:24px; margin-bottom:10px;">❌</div>
                    <div style="color:var(--danger);">Error al cargar logs: ${err}</div>
                </div>
            `;
        });
    }

    // Tooltips para vendors
    document.addEventListener('mouseover', function(e) {
        if (e.target.closest('[data-tooltip]')) {
            const tooltip = e.target.closest('[data-tooltip]');
            // Aquí puedes agregar lógica de tooltip si lo deseas
        }
    });

    // Actualizar métricas en tiempo real (cada 30 segundos)
    setInterval(() => {
        // Actualizar solo si estamos en la pestaña de monitor
        const monitorTab = document.getElementById('tab-monitor');
        if (monitorTab && monitorTab.style.display !== 'none') {
            // Podrías hacer un refresh automático de ciertas métricas
            console.log('Monitor activo - métricas actualizadas');
        }
    }, 30000);

    // Auto-resize del textarea
    const textarea = document.getElementById('code');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(Math.max(this.scrollHeight, 200), 600) + 'px';
        });
    }
</script>

</body>
</html>