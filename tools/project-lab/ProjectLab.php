<?php

// FILE: tools/project-lab/ProjectLab.php |V1

/**
 * PROJECT LAB - Clase Principal
 * Maneja toda la lógica del dashboard
 */
class ProjectLab
{
    private $projectRoot;

    private $labRoot;

    private $logFile;

    private $csrfToken;

    private $rateLimitFile;

    public function __construct($projectRoot, $labRoot)
    {
        $this->projectRoot = $projectRoot;
        $this->labRoot = $labRoot;
        $this->logFile = $projectRoot.'/storage/logs/project-lab.log';
        $this->rateLimitFile = sys_get_temp_dir().'/projectlab_ratelimit.json';

        $this->ensureDirectories();
        $this->initSession();
    }

    private function ensureDirectories()
    {
        $dirs = [
            dirname($this->logFile),
            $this->projectRoot.'/storage/framework/cache',
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
    }

    private function initSession()
    {
        if (! isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->csrfToken = $_SESSION['csrf_token'];
    }

    public function handleRequest()
    {
        // Seguridad: Solo local
        if (config('app.env') !== 'local') {
            $this->jsonResponse(['error' => 'Acceso denegado'], 403);
        }

        // Verificar CSRF en POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            $this->checkRateLimit();
        }

        // Procesar acciones
        $this->processActions();

        // Renderizar vista
        $this->render();
    }

    private function verifyCsrf()
    {
        $token = $_POST['csrf_token'] ?? '';
        if (! hash_equals($this->csrfToken, $token)) {
            $this->jsonResponse(['error' => 'CSRF token inválido'], 419);
        }
    }

    private function checkRateLimit()
    {
        $data = json_decode(@file_get_contents($this->rateLimitFile), true)
                ?? ['count' => 0, 'reset' => time() + 3600];

        if (time() > $data['reset']) {
            $data = ['count' => 0, 'reset' => time() + 3600];
        }

        $data['count']++;

        if ($data['count'] > 50) {
            $this->jsonResponse(['error' => 'Rate limit excedido'], 429);
        }

        file_put_contents($this->rateLimitFile, json_encode($data));
    }

    private function processActions()
    {
        $output = '';
        $code = $_POST['code'] ?? '';

        // Ejecutar Tinker
        if (isset($_POST['run']) && ! empty($code)) {
            $output = $this->executeTinker($code);
        }

        // Comandos Artisan
        if (isset($_POST['artisan'])) {
            $output = $this->executeArtisan($_POST['artisan']);
        }

        // Generar modelo (AJAX)
        if (isset($_POST['generate_model'])) {
            $this->generateModel($_POST['generate_model']);
        }

        // Describir tabla (AJAX)
        if (isset($_POST['describe_table'])) {
            $this->describeTable($_POST['describe_table']);
        }

        // Tail logs (AJAX)
        if (isset($_POST['tail_logs'])) {
            $this->tailLogs($_POST['lines'] ?? 50);
        }

        // Ejecutar script
        if (isset($_POST['run_script'])) {
            $this->executeScript($_POST['run_script']);
        }

        $this->output = $output;
        $this->code = $code;
    }

    private function executeTinker($code)
    {
        $wrappedCode = "
            \\DB::enableQueryLog();
            \$start = microtime(true);
            try {
                \$result = (function() { 
                    return {$code}; 
                })(); 
                
                if (isset(\$result)) {
                    echo \"--- RESULTADO ---\\n\";
                    print_r(\$result);
                }
            } catch (\\Throwable \$e) {
                echo 'ERROR: ' . \$e->getMessage();
            }
            \$queries = \\DB::getQueryLog();
            \$time = round((microtime(true) - \$start) * 1000, 2);
            echo \"\\n\\n--- SQL DEPURACIÓN (\" . count(\$queries) . \" consultas, {\$time}ms) ---\\n\";
            foreach(\$queries as \$q) {
                echo \"[\".\$q['time'].\"ms] \".\$q['query'].\" | Binds: \".json_encode(\$q['bindings']).\"\\n\";
            }
        ";

        $command = 'cd '.escapeshellarg($this->projectRoot).
                   ' && php artisan tinker --execute='.escapeshellarg($wrappedCode);

        $output = shell_exec($command.' 2>&1');

        // Guardar en log
        $this->log('TINKER', $code, $output);

        // Guardar historial
        $this->saveHistory($code, $output);

        return $output;
    }

    private function executeArtisan($command)
    {
        $output = shell_exec('cd '.escapeshellarg($this->projectRoot).
                            ' && php artisan '.$command.' 2>&1');
        $this->log('ARTISAN', $command, $output);

        return $output;
    }

    private function generateModel($name)
    {
        $name = preg_replace('/[^a-zA-Z]/', '', $name);
        if (empty($name)) {
            $this->jsonResponse(['error' => 'Nombre inválido'], 400);
        }

        $output = shell_exec('cd '.escapeshellarg($this->projectRoot).
                            ' && php artisan make:model '.escapeshellarg($name).
                            ' -mfs 2>&1');

        $this->log('GENERATE', "Modelo: {$name}", $output);
        echo $output;
        exit;
    }

    private function describeTable($tableName)
    {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);

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

    private function tailLogs($lines = 50)
    {
        $logFile = $this->projectRoot.'/storage/logs/laravel.log';
        if (file_exists($logFile)) {
            $output = shell_exec('tail -n '.intval($lines).' '.
                                escapeshellarg($logFile).' 2>&1');
            echo "<pre style='background:#000; color:#10b981; padding:15px; max-height:400px; overflow-y:auto; font-size:11px;'>";
            echo htmlspecialchars($output ?: 'Log vacío');
            echo '</pre>';
        } else {
            echo 'No se encontró el archivo de log.';
        }
        exit;
    }

    private function executeScript($script)
    {
        $allowed = ['docs.sh', 'codigos.sh'];

        if (! in_array($script, $allowed)) {
            echo 'Error: Script no permitido';
            exit;
        }

        $path = $this->projectRoot.'/'.$script;
        if (! file_exists($path)) {
            echo "Error: El archivo {$script} no existe";
            exit;
        }

        $output = shell_exec('cd '.escapeshellarg($this->projectRoot).
                            ' && sh '.escapeshellarg($script).' 2>&1');

        echo "--- SALIDA DE $script ---\n".$output;
        $this->log('SCRIPT', $script, $output);
        exit;
    }

    private function log($type, $command, $output)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents(
            $this->logFile,
            "[{$timestamp}] {$type}: {$command}\n{$output}\n\n",
            FILE_APPEND
        );
    }

    private function saveHistory($code, $output)
    {
        $historyFile = $this->projectRoot.'/storage/logs/tinker_history.json';
        $history = json_decode(@file_get_contents($historyFile), true) ?? [];

        array_unshift($history, [
            'code' => $code,
            'preview' => substr($code, 0, 50).(strlen($code) > 50 ? '...' : ''),
            'timestamp' => date('Y-m-d H:i:s'),
            'success' => strpos($output, 'ERROR:') === false,
        ]);

        $history = array_slice($history, 0, 15);
        file_put_contents($historyFile, json_encode($history));
    }

    private function getTablesInfo()
    {
        $cacheFile = $this->projectRoot.'/storage/framework/cache/projectlab_tables.cache';

        // Intentar caché (5 minutos)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        $tables = [];
        try {
            $rawTables = array_map('current', DB::select('SHOW TABLES'));
            foreach ($rawTables as $t) {
                $tables[] = [
                    'name' => $t,
                    'count' => DB::table($t)->count(),
                ];
            }
            file_put_contents($cacheFile, json_encode($tables));
        } catch (Exception $e) {
            // Silencio
        }

        return $tables;
    }

    private function getRoutes()
    {
        $cacheFile = $this->projectRoot.'/storage/framework/cache/projectlab_routes.cache';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 600) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        $routes = [];
        try {
            Artisan::call('route:list', ['--json' => true]);
            $routes = json_decode(Artisan::output(), true) ?? [];
            file_put_contents($cacheFile, json_encode($routes));
        } catch (Exception $e) {
            // Silencio
        }

        return $routes;
    }

    private function getSystemInfo()
    {
        return [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'disk_free' => round(disk_free_space($this->projectRoot) / 1024 / 1024 / 1024, 2),
            'disk_total' => round(disk_total_space($this->projectRoot) / 1024 / 1024 / 1024, 2),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'db_driver' => config('database.default'),
            'db_database' => config('database.connections.'.config('database.default').'.database'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
            'packages_count' => $this->getPackagesCount(),
            'vendors_count' => $this->getVendorsCount(),
        ];
    }

    private function render()
    {
        // Preparar datos para las vistas
        $data = [
            'csrfToken' => $this->csrfToken,
            'output' => $this->output ?? '',
            'code' => $this->code ?? '',
            'tablesInfo' => $this->getTablesInfo(),
            'routes' => $this->getRoutes(),
            'systemInfo' => $this->getSystemInfo(),
            'history' => json_decode(@file_get_contents(
                $this->projectRoot.'/storage/logs/tinker_history.json'
            ), true) ?? [],
            'rateLimitData' => json_decode(@file_get_contents($this->rateLimitFile), true) ?? [
                'count' => 0,
                'reset' => time() + 3600,
            ],
            // Datos específicos para el monitor
            'dbInfo' => $this->getDatabaseInfo(),
            'cacheDrivers' => $this->getCacheDrivers(),
            'availableDrivers' => $this->getAvailableDrivers(),
            'folderSizes' => $this->getFolderSizes(),
            'installedFeatured' => $this->getInstalledFeatured(),
            'vendorCounts' => $this->getVendorCounts(),
            'topVendors' => $this->getTopVendors(),
            'totalPackages' => $this->getPackagesCount(),
            'totalVendors' => $this->getVendorsCount(),
            'projectRoot' => $this->projectRoot,
            'labRoot' => $this->labRoot,
        ];

        // Extraer variables para la vista
        extract($data);

        // Cargar layout principal
        require $this->labRoot.'/views/layout.php';
    }

    // Métodos adicionales necesarios
    private function getDatabaseInfo()
    {
        $connection = config('database.default');

        return [
            'driver' => $connection,
            'host' => config("database.connections.{$connection}.host"),
            'database' => config("database.connections.{$connection}.database"),
            'charset' => config("database.connections.{$connection}.charset"),
        ];
    }

    private function getCacheDrivers()
    {
        return [
            'Cache Default' => config('cache.default'),
            'Session' => config('session.driver'),
            'Queue' => config('queue.default'),
            'Filesystem' => config('filesystems.default'),
        ];
    }

    private function getAvailableDrivers()
    {
        return [
            'PDO Drivers' => extension_loaded('pdo') ? implode(', ', PDO::getAvailableDrivers()) : 'No disponible',
            'Redis' => extension_loaded('redis') ? '✅ Disponible' : '❌ No instalado',
            'Memcached' => extension_loaded('memcached') ? '✅ Disponible' : '❌ No instalado',
            'GD/Imagick' => extension_loaded('gd') ? 'GD ✅' : (extension_loaded('imagick') ? 'Imagick ✅' : '❌ Ninguno'),
            'OPcache' => extension_loaded('Zend OPcache') ? '✅ Disponible' : '❌ No instalado',
            'Xdebug' => extension_loaded('xdebug') ? '✅ Disponible' : '❌ No instalado',
        ];
    }

    private function getFolderSizes()
    {
        $folders = ['storage', 'vendor', 'node_modules', 'public'];
        $sizes = [];

        foreach ($folders as $folder) {
            $path = $this->projectRoot.'/'.$folder;
            if (is_dir($path)) {
                $size = $this->calculateFolderSize($path);
                $sizes[$folder] = round($size / 1024 / 1024, 2); // MB
            }
        }

        return $sizes;
    }

    private function calculateFolderSize($dir)
    {
        $size = 0;
        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->calculateFolderSize($each);
        }

        return $size;
    }

    private function getInstalledFeatured()
    {
        $lockFile = $this->projectRoot.'/composer.lock';
        if (! file_exists($lockFile)) {
            return [];
        }

        $data = json_decode(file_get_contents($lockFile), true);
        $packages = $data['packages'] ?? [];

        $featured = [
            'spatie/laravel-permission' => 'Gestión de roles y permisos',
            'barryvdh/laravel-debugbar' => 'Debugbar para desarrollo',
            'maatwebsite/excel' => 'Exportación/Importación Excel',
            'laravel/sanctum' => 'Autenticación API Tokens',
            'laravel/horizon' => 'Monitor de colas Redis',
            'livewire/livewire' => 'Componentes full-stack',
            'inertiajs/inertia-laravel' => 'Adapter SPA moderno',
            'laravel/telescope' => 'Debugger elegante',
            'nunomaduro/collision' => 'Manejo de errores mejorado',
            'laravel/pint' => 'Formateador de código',
        ];

        $installed = [];
        foreach ($featured as $package => $description) {
            foreach ($packages as $pkg) {
                if ($pkg['name'] === $package) {
                    $installed[$package] = [
                        'version' => $pkg['version'] ?? 'N/A',
                        'description' => $description,
                    ];
                    break;
                }
            }
        }

        return $installed;
    }

    private function getVendorCounts()
    {
        $lockFile = $this->projectRoot.'/composer.lock';
        if (! file_exists($lockFile)) {
            return [];
        }

        $data = json_decode(file_get_contents($lockFile), true);
        $vendors = [];

        foreach ($data['packages'] ?? [] as $pkg) {
            $v = explode('/', $pkg['name'])[0];
            $vendors[$v] = ($vendors[$v] ?? 0) + 1;
        }

        arsort($vendors);

        return $vendors;
    }

    private function getTopVendors()
    {
        $vendors = $this->getVendorCounts();

        return array_slice($vendors, 0, 20);
    }

    private function getPackagesCount()
    {
        $lockFile = $this->projectRoot.'/composer.lock';
        if (file_exists($lockFile)) {
            $data = json_decode(file_get_contents($lockFile), true);

            return count($data['packages'] ?? []);
        }

        return 0;
    }

    private function getVendorsCount()
    {
        $vendors = $this->getVendorCounts();

        return count($vendors);
    }

    private function jsonResponse($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}
