<?php

// FILE: tools/project-lab/ProjectLab.php |V1

/**
 * PROJECT LAB - Clase Principal
 * Maneja toda la lógica del dashboard
 */
class ProjectLab
{
    private const CODE_LOG_FILE = 'documentos/log/code-updates.log';

    private const ASSET_SECTION_EXTENSIONS = [
        'css',
        'js',
    ];

    private const REGEX_TARGET_OPERATION = '/^\/\/\s*(?:TARGET|FILE):\s*(.+?)\s*(::|\+\+)\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*$/';

    private const REGEX_PHP_FILE_HEADER = '/^\/\/\s*FILE:\s*(.+?)(?:\s*\|\s*(V\d+))?\s*$/';

    private const REGEX_BLADE_FILE_HEADER = '/^\{\{\-\-\s*FILE:\s*(.+?)(?:\s*\|\s*(V\d+))?\s*\-\-\}\}$/';

    private const REGEX_PLAIN_FILE_HEADER_LINE = '/^\/\/\s*FILE:\s*(.+?)(?:\s*\|\s*(V\d+))?\s*$/';

    private const REGEX_PLAIN_FILE_HEADER_BLOCK = '/^\/\*\s*FILE:\s*(.+?)(?:\s*\|\s*(V\d+))?\s*\*\/$/';

    private const REGEX_ASSET_SECTION_BLOCK = '/REEMPLAZAR EN:\s*\[?(.+?\.(?:css|js))\]?\s*(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su';

    private const REGEX_SECTION_NAME = '/<<SECTION:\s*(.*?)\s*>>/su';

    private const REGEX_METHOD_SIGNATURE_TEMPLATE = '/^[ \t]*(?:public|protected|private)\s+function\s+%s\s*\(/m';

    private const REGEX_JS_FUNCTION_SIGNATURE_TEMPLATE = '/(?:^|\n)(?:export\s+)?(?:async\s+)?function\s+%s\s*\(/';

    private $projectRoot;

    private $labRoot;

    private $logFile;

    private $csrfToken;

    private $rateLimitFile;

    public function __construct($projectRoot, $labRoot)
    {
        $this->projectRoot = $projectRoot;
        $this->labRoot = $labRoot;
        $this->logFile = $projectRoot.'/documentos/log/project-lab.log';
        $this->rateLimitFile = sys_get_temp_dir().'/projectlab_ratelimit.json';

        $this->ensureDirectories();
        $this->initSession();
    }

    private function ensureDirectories()
    {
        $dirs = [
            dirname($this->logFile),
            $this->projectRoot.'/storage/framework/cache',
            $this->projectRoot.'/documentos/log',
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

        if (isset($_POST['ajax_lab_tool'])) {
            $labTool = (string) ($_POST['lab_tool'] ?? '');
            $fromClipboard = isset($_POST['from_clipboard']) && $_POST['from_clipboard'] === '1';

            $labInput = $fromClipboard
                ? $this->readClipboard()
                : (string) ($_POST['lab_input'] ?? '');

            if ($labTool === 'code') {
                $output = $this->runEmbeddedCodeTool($labInput);
            } elseif ($labTool === 'docs') {
                $output = $this->runEmbeddedDocsTool($labInput);
            } elseif ($labTool === 'audit') {
                $output = $this->runAuditScript($labInput);
            } else {
                $output = "[ERROR] Herramienta Lab no reconocida: {$labTool}";
            }

            $this->jsonResponse([
                'ok' => true,
                'tool' => $labTool,
                'input' => $labInput,
                'output' => $output,
            ]);
        }

        if (isset($_POST['ajax_artisan'])) {
            $command = (string) ($_POST['artisan'] ?? '');

            if (trim($command) === '') {
                $this->jsonResponse([
                    'ok' => false,
                    'output' => '[ERROR] No se recibió comando Artisan.',
                ]);
            }

            $output = $this->executeArtisan($command);

            $this->jsonResponse([
                'ok' => true,
                'command' => $command,
                'output' => $output,
            ]);
        }

        if (isset($_POST['ajax_tinker'])) {
            $code = (string) ($_POST['code'] ?? '');

            if (trim($code) === '') {
                $this->jsonResponse([
                    'ok' => false,
                    'output' => '[ERROR] No se recibió código Tinker.',
                ]);
            }

            $output = $this->executeTinker($code);

            $this->jsonResponse([
                'ok' => true,
                'code' => $code,
                'output' => $output,
            ]);
        }

        if (isset($_POST['run']) && ! empty($code)) {
            $output = $this->executeTinker($code);
        }

        if (isset($_POST['artisan'])) {
            $output = $this->executeArtisan($_POST['artisan']);
        }

        if (isset($_POST['lab_tool'])) {
            $labTool = (string) ($_POST['lab_tool'] ?? '');
            $fromClipboard = isset($_POST['from_clipboard']);

            $labInput = $fromClipboard
                ? $this->readClipboard()
                : (string) ($_POST['lab_input'] ?? '');

            if ($labTool === 'code') {
                $output = $this->runEmbeddedCodeTool($labInput);
            } elseif ($labTool === 'docs') {
                $output = $this->runEmbeddedDocsTool($labInput);
            } else {
                $output = "[ERROR] Herramienta Lab no reconocida: {$labTool}";
            }

            $_SESSION['project_lab_tool_input'] = $labInput;
            $_SESSION['project_lab_tool_output'] = $output;
            $_SESSION['project_lab_tool_active'] = $labTool;
        }

        if (isset($_POST['generate_model'])) {
            $this->generateModel($_POST['generate_model']);
        }

        if (isset($_POST['lab_audit_clipboard'])) {
            $output = $this->runAuditFromClipboard();

            $_SESSION['project_lab_tool_output'] = $output;
            $_SESSION['project_lab_tool_input'] = '';
            $_SESSION['project_lab_tool_active'] = 'audit';
        }

        if (isset($_POST['describe_table'])) {
            $this->describeTable($_POST['describe_table']);
        }

        if (isset($_POST['tail_logs'])) {
            $this->tailLogs($_POST['lines'] ?? 50);
        }

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
        $allowed = ['docs.sh', 'codigos.sh', 'auditar.sh'];

        if (! in_array($script, $allowed)) {
            echo 'Error: Script no permitido';
            exit;
        }

        $path = $this->projectRoot.'/tools/'.$script;
        if (! file_exists($path)) {
            echo "Error: El archivo {$script} no existe";
            exit;
        }

        $output = shell_exec('cd '.escapeshellarg($this->projectRoot).
                            ' && bash '.escapeshellarg('tools/'.$script).' 2>&1');

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
    $data = [
        'csrfToken' => $this->csrfToken,
        'output' => $this->output ?? '',
        'code' => $this->code ?? '',
        'labToolInput' => $_SESSION['project_lab_tool_input'] ?? '',
        'labToolOutput' => $_SESSION['project_lab_tool_output'] ?? '',
        'labToolActive' => $_SESSION['project_lab_tool_active'] ?? 'code',
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
        'dbInfo' => $this->getDatabaseInfo(),
        'cacheDrivers' => $this->getCacheDrivers(),
        'availableDrivers' => $this->getAvailableDrivers(),
        'folderSizes' => $this->getFolderSizes(),
        'installedFeatured' => $this->getInstalledFeatured(),
        'vendorCounts' => $this->getVendorCounts(),
        'topVendors' => $this->getTopVendors(),
        'totalPackages' => $this->getPackagesCount(),
        'totalVendors' => $this->getVendorsCount(),
        'assetSectionsCatalog' => $this->getAssetSectionsCatalog(),
        'projectRoot' => $this->projectRoot,
        'labRoot' => $this->labRoot,
    ];

    extract($data);

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

private function runEmbeddedCodeTool(string $input): string
{
    $input = str_replace(["\r\n", "\r"], "\n", trim($input));

    if ($input === '') {
        return '[ERROR] No se recibió contenido para actualizar código.';
    }

    if (preg_match(self::REGEX_ASSET_SECTION_BLOCK, $input)) {
        return $this->runEmbeddedAssetSectionsTool($input);
    }

    $methodOperation = $this->parseEmbeddedMethodOperation($input);

    if ($methodOperation !== null) {
        $extension = strtolower(pathinfo($methodOperation['path'], PATHINFO_EXTENSION));

        if ($extension === 'js') {
            if ($methodOperation['operation'] !== 'replace') {
                return '[ERROR] En JS solo está soportado TARGET :: función. Use secciones JS para agregar bloques nuevos.';
            }

            return $this->applyEmbeddedJsFunctionReplace($methodOperation);
        }

        if ($methodOperation['operation'] === 'replace') {
            return $this->applyEmbeddedMethodReplace($methodOperation);
        }

        if ($methodOperation['operation'] === 'add') {
            return $this->applyEmbeddedMethodAdd($methodOperation);
        }

        return '[ERROR] Operación TARGET no soportada.';
    }

    if (str_starts_with(ltrim($input), '<?php')) {
        return $this->applyEmbeddedPhpFile($input);
    }

    if (preg_match('/^\s*\{\{\-\-\s*FILE:/', $input)) {
        return $this->applyEmbeddedBladeFile($input);
    }

    if (preg_match('/^\s*\/\*\s*FILE:\s*.+?\.css(?:\s*\|\s*V\d+)?\s*\*\//', $input)) {
        return $this->applyEmbeddedPlainFile($input, 'css_full');
    }

    if (
        preg_match('/^\s*\/\/\s*FILE:\s*.+?\.js(?:\s*\|\s*V\d+)?\s*$/m', $input)
        || preg_match('/^\s*\/\*\s*FILE:\s*.+?\.js(?:\s*\|\s*V\d+)?\s*\*\//', $input)
    ) {
        return $this->applyEmbeddedPlainFile($input, 'js_full');
    }

    return "[ERROR] Formato no compatible en herramienta de código.\n[INFO] Soporta PHP completo, Blade completo, CSS completo, JS completo, secciones CSS/JS, TARGET :: método PHP, TARGET ++ método PHP y TARGET :: función JS.";
}

    private function applyEmbeddedBladeFile(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", ltrim($content));
        $headerLine = trim($lines[0] ?? '');

        if (! preg_match(self::REGEX_BLADE_FILE_HEADER, $headerLine, $matches)) {
            return '[ERROR] No se encontró encabezado FILE válido para Blade.';
        }

        $relativePath = trim($matches[1] ?? '');
        $version = trim($matches[2] ?? 'V1');

        if ($relativePath === '') {
            return '[ERROR] Ruta destino vacía.';
        }

        $targetPath = $this->projectRoot.'/'.$relativePath;
        $directory = dirname($targetPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        array_shift($lines);
        $body = ltrim(implode("\n", $lines), "\n");

        $finalContent = "{{-- FILE: {$relativePath} | {$version} --}}\n\n".$body;
        $status = file_exists($targetPath) ? 'sobrescrito' : 'creado';

        if (file_put_contents($targetPath, $finalContent) === false) {
            return "[ERROR] No se pudo escribir el archivo: {$relativePath}";
        }

        $message = "[OK] Modo: blade_full\n";
        $message .= "[OK] Archivo: {$relativePath}\n";

        if ($status === 'sobrescrito') {
            $message .= "[WARN] Archivo existente reemplazado completamente.\n";
        } else {
            $message .= "[INFO] Archivo nuevo creado.\n";
        }

        $message .= "[OK] Estado: {$status}\n";
        $message .= "[OK] Versión: {$version}";

        $this->log('LAB_CODE', $relativePath, $message);

        $this->appendPipeLog(
            self::CODE_LOG_FILE,
            ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
            [
                $relativePath,
                date('Y-m-d H:i:s'),
                $status,
                $version,
                'project_lab',
                'archivo_completo',
                get_current_user() ?: 'unknown',
                php_uname('n') ?: 'unknown',
            ]
        );

        return $message;
    }

private function applyEmbeddedPhpFile(string $content): string
{
    $content = str_replace(["\r\n", "\r"], "\n", $content);

    $lines = explode("\n", ltrim($content));

    if (trim($lines[0] ?? '') !== '<?php') {
        return '[ERROR] El archivo PHP completo debe comenzar con <?php.';
    }

    array_shift($lines);

    while (! empty($lines) && trim($lines[0]) === '') {
        array_shift($lines);
    }

    $headerLine = trim($lines[0] ?? '');

    if (! preg_match(self::REGEX_PHP_FILE_HEADER, $headerLine, $matches)) {
        return '[ERROR] No se encontró encabezado FILE válido para PHP.';
    }

    $relativePath = trim($matches[1] ?? '');
    $version = trim($matches[2] ?? 'V1');

    if ($relativePath === '') {
        return '[ERROR] Ruta destino vacía.';
    }

    $targetPath = $this->projectRoot.'/'.$relativePath;
    $directory = dirname($targetPath);

    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    array_shift($lines);

    $body = ltrim(implode("\n", $lines), "\n");

    $finalContent = "<?php\n\n// FILE: {$relativePath} | {$version}\n\n".$body;
    $status = file_exists($targetPath) ? 'sobrescrito' : 'creado';

    if (file_put_contents($targetPath, $finalContent) === false) {
        return "[ERROR] No se pudo escribir el archivo: {$relativePath}";
    }

    $message = "[OK] Modo: php_full\n";
    $message .= "[OK] Archivo: {$relativePath}\n";

    if ($status === 'sobrescrito') {
        $message .= "[WARN] Archivo existente reemplazado completamente.\n";
    } else {
        $message .= "[INFO] Archivo nuevo creado.\n";
    }

    $message .= "[OK] Estado: {$status}\n";
    $message .= "[OK] Versión: {$version}";

    $this->log('LAB_CODE', $relativePath, $message);

    $this->appendPipeLog(
        self::CODE_LOG_FILE,
        ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
        [
            $relativePath,
            date('Y-m-d H:i:s'),
            $status,
            $version,
            'project_lab',
            'archivo_completo',
            get_current_user() ?: 'unknown',
            php_uname('n') ?: 'unknown',
        ]
    );

    return $message;
}

    private function runEmbeddedDocsTool(string $input): string
    {
        $input = str_replace(["\r\n", "\r"], "\n", trim($input));

        if ($input === '') {
            return '[ERROR] No se recibió contenido para actualizar documentos.';
        }

        preg_match_all(
            '/REEMPLAZAR EN:\s*\[?([a-z0-9_]+)\]?\s*(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su',
            $input,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            return "[ERROR] No se encontraron bloques válidos.\n[INFO] Formato esperado: REEMPLAZAR EN: [doc_slug] + bloque SECTION completo.";
        }

        $documents = $this->indexEmbeddedDocuments();
        $total = 0;
        $output = '';

        foreach ($matches as $match) {
            $slug = strtolower(trim($match[1]));
            $block = trim($match[2]);

            if (! isset($documents[$slug])) {
                $output .= "[ERROR] Documento no reconocido por slug: {$slug}\n";

                continue;
            }

            if (! preg_match('/<<SECTION:\s*(.*?)\s*>>/su', $block, $sectionMatch)) {
                $output .= "[ERROR] No se pudo leer el nombre de sección para {$slug}\n";

                continue;
            }

            $sectionName = trim($sectionMatch[1]);
            $filePath = $documents[$slug];

            $content = file_get_contents($filePath);

            if ($content === false) {
                $output .= "[ERROR] No se pudo leer documento: {$slug}\n";

                continue;
            }

            $pattern = '/<<SECTION:\s*'.preg_quote($sectionName, '/').'\s*>>.*?<<END SECTION>>/su';

            if (! preg_match($pattern, $content)) {
                $output .= "[ERROR] [{$slug}] No se encontró la sección: {$sectionName}\n";

                continue;
            }

            $backupDir = $this->projectRoot.'/documentos/baks';

            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0775, true);
            }

            file_put_contents($backupDir.'/'.basename($filePath).'.bak', $content);

            $newContent = preg_replace($pattern, $block, $content, 1, $count);

            if ($count < 1 || $newContent === null) {
                $output .= "[ERROR] [{$slug}] No se pudo reemplazar la sección: {$sectionName}\n";

                continue;
            }

            if (file_put_contents($filePath, $newContent) === false) {
                $output .= "[ERROR] [{$slug}] No se pudo escribir el documento.\n";

                continue;
            }

            $total++;
            $output .= "[OK] [{$slug}] Sección reemplazada: {$sectionName}\n";
        }

        $output .= $total > 0
            ? "[OK] Proceso finalizado. Secciones aplicadas: {$total}"
            : '[WARN] Proceso finalizado sin cambios aplicados.';

        $this->log('LAB_DOCS', 'embedded-docs', $output);

        $this->appendPipeLog(
            'documentos/log/docs-updates.log',
            ['DOCUMENTO', 'FECHA', 'SECCIONES', 'USUARIO', 'HOST', 'ORIGEN'],
            [
                $slug,
                date('Y-m-d H:i:s'),
                '1',
                get_current_user() ?: 'unknown',
                php_uname('n') ?: 'unknown',
                'project_lab',
            ]
        );

        return $output;
    }

    private function indexEmbeddedDocuments(): array
    {
        $baseDir = $this->projectRoot.'/documentos';
        $documents = [];

        foreach (glob($baseDir.'/*.txt') ?: [] as $filePath) {
            $content = file_get_contents($filePath);

            if ($content === false) {
                continue;
            }

            if (preg_match('/DOC_SLUG:\s*([a-z0-9_]+)/', $content, $match)) {
                $documents[strtolower(trim($match[1]))] = $filePath;
            }
        }

        return $documents;
    }

    private function parseEmbeddedMethodOperation(string $input): ?array
    {
        $lines = explode("\n", $input);
        $header = trim($lines[0] ?? '');

        if (! preg_match(self::REGEX_TARGET_OPERATION, $header, $matches)) {
            return null;
        }

        array_shift($lines);

        $path = trim($matches[1]);
        $operator = trim($matches[2]);
        $methodName = trim($matches[3]);
        $methodCode = trim(implode("\n", $lines));

        if ($path === '' || $methodName === '' || $methodCode === '') {
            return null;
        }

        return [
            'operation' => $operator === '::' ? 'replace' : 'add',
            'path' => $path,
            'method_name' => $methodName,
            'method_code' => $methodCode,
        ];
    }

    private function applyEmbeddedMethodReplace(array $operation): string
    {
        $relativePath = $operation['path'];
        $methodName = $operation['method_name'];
        $methodCode = $operation['method_code'];
        $targetPath = $this->projectRoot.'/'.$relativePath;

        if (! file_exists($targetPath)) {
            return "[ERROR] El archivo destino no existe: {$relativePath}";
        }

        $content = file_get_contents($targetPath);

        if ($content === false) {
            return "[ERROR] No se pudo leer el archivo: {$relativePath}";
        }

        $range = $this->findEmbeddedMethodRange($content, $methodName);

        if ($range === null) {
            return "[ERROR] No se encontró el método: {$methodName}";
        }

        [$start, $end] = $range;

        $newContent = substr($content, 0, $start)
            .rtrim($methodCode)
            .substr($content, $end);

        if (file_put_contents($targetPath, $newContent) === false) {
            return "[ERROR] No se pudo escribir el archivo: {$relativePath}";
        }

        $message = "[OK] Modo: php_method_patch\n";
        $message .= "[OK] Archivo: {$relativePath}\n";
        $message .= "[OK] Método reemplazado: {$methodName}";

        $this->log('LAB_CODE_METHOD_REPLACE', "{$relativePath}::{$methodName}", $message);
        $this->appendPipeLog(
            'documentos/log/code-updates.log',
            ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
            [
                $relativePath,
                date('Y-m-d H:i:s'),
                $status ?? 'actualizado',
                $version ?? '-',
                'project_lab',
                $methodName ?? 'archivo_completo',
                get_current_user() ?: 'unknown',
                php_uname('n') ?: 'unknown',
            ]
        );

        return $message;
    }

    private function applyEmbeddedMethodAdd(array $operation): string
    {
        $relativePath = $operation['path'];
        $methodName = $operation['method_name'];
        $methodCode = $operation['method_code'];
        $targetPath = $this->projectRoot.'/'.$relativePath;

        if (! file_exists($targetPath)) {
            return "[ERROR] El archivo destino no existe: {$relativePath}";
        }

        $content = file_get_contents($targetPath);

        if ($content === false) {
            return "[ERROR] No se pudo leer el archivo: {$relativePath}";
        }

        if ($this->findEmbeddedMethodRange($content, $methodName) !== null) {
            return "[ERROR] El método ya existe: {$methodName}";
        }

        $lastBrace = strrpos($content, '}');

        if ($lastBrace === false) {
            return "[ERROR] No se pudo encontrar cierre de clase en: {$relativePath}";
        }

        $insert = "\n\n    ".str_replace("\n", "\n    ", trim($methodCode))."\n";

        $newContent = substr($content, 0, $lastBrace)
            .$insert
            .substr($content, $lastBrace);

        if (file_put_contents($targetPath, $newContent) === false) {
            return "[ERROR] No se pudo escribir el archivo: {$relativePath}";
        }

        $message = "[OK] Modo: php_method_add\n";
        $message .= "[OK] Archivo: {$relativePath}\n";
        $message .= "[OK] Método agregado: {$methodName}";

        $this->log('LAB_CODE_METHOD_ADD', "{$relativePath}++{$methodName}", $message);
        $this->appendPipeLog(
            'documentos/log/code-updates.log',
            ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
            [
                $relativePath,
                date('Y-m-d H:i:s'),
                $status ?? 'actualizado',
                $version ?? '-',
                'project_lab',
                $methodName ?? 'archivo_completo',
                get_current_user() ?: 'unknown',
                php_uname('n') ?: 'unknown',
            ]
        );

        return $message;
    }

    private function findEmbeddedMethodRange(string $content, string $methodName): ?array
    {
        $pattern = sprintf(
            self::REGEX_METHOD_SIGNATURE_TEMPLATE,
            preg_quote($methodName, '/')
        );

        if (! preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start = $match[0][1];
        $slice = substr($content, $start);
        $tokens = token_get_all("<?php\n".$slice);

        $offset = $start;
        $depth = 0;
        $started = false;

        foreach ($tokens as $token) {
            $text = is_array($token) ? $token[1] : $token;

            if ($text === "<?php\n") {
                continue;
            }

            $length = strlen($text);

            if ($text === '{') {
                $depth++;
                $started = true;
            } elseif ($text === '}') {
                $depth--;

                if ($started && $depth === 0) {
                    return [$start, $offset + $length];
                }
            }

            $offset += $length;
        }

        return null;
    }

    private function readClipboard(): string
    {
        $commands = [
            'xclip -selection clipboard -o 2>/dev/null',
            'wl-paste 2>/dev/null',
            'pbpaste 2>/dev/null',
        ];

        foreach ($commands as $command) {
            $output = shell_exec($command);

            if (is_string($output) && trim($output) !== '') {
                return $output;
            }
        }

        return '';
    }

    private function runAuditFromClipboard(): string
    {
        return $this->runAuditScript($this->readClipboard());
    }

    private function appendPipeLog(string $relativeLogPath, array $columns, array $row): void
    {
        $logPath = $this->projectRoot.'/'.$relativeLogPath;
        $logDir = dirname($logPath);

        if (! is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $isNewFile = ! file_exists($logPath);

        $content = '';

        if ($isNewFile) {
            $content .= implode(' | ', $columns)."\n";
        }

        $content .= implode(' | ', $row)."\n";

        file_put_contents($logPath, $content, FILE_APPEND);
    }

    private function executeAuditScript(string $script): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'project_lab_audit_');

        if ($tmpFile === false) {
            return '[ERROR] No se pudo crear archivo temporal.';
        }

        file_put_contents($tmpFile, $script);

        $command = 'cd '.escapeshellarg($this->projectRoot)
            .' && bash '.escapeshellarg($tmpFile).' 2>&1';

        $output = shell_exec($command);

        @unlink($tmpFile);

        return is_string($output) ? $output : '';
    }

    private function runAuditScript(string $script): string
    {
        $script = trim($script);

        if ($script === '') {
            return '[ERROR] El contenido de auditoría está vacío.';
        }

        $auditDir = $this->projectRoot.'/documentos/auditoria';

        if (! is_dir($auditDir)) {
            mkdir($auditDir, 0775, true);
        }

        $hasExplicitAuditOutput = str_contains($script, 'documentos/auditoria/');

        if ($hasExplicitAuditOutput) {
            $result = $this->executeAuditScript($script);

            $message = "[OK] Auditoría ejecutada.\n";
            $message .= "[OK] Proyecto: {$this->projectRoot}\n\n";
            $message .= $result ?: '[OK] Sin salida directa. Revisar archivo generado en documentos/auditoria/.';

            $this->log('LAB_AUDIT', 'audit-explicit-output', $message);

            return $message;
        }

        $timestamp = date('Ymd_His');
        $relativeOutput = "documentos/auditoria/auditoria_{$timestamp}.txt";
        $outputPath = $this->projectRoot.'/'.$relativeOutput;

        $result = $this->executeAuditScript($script);

        file_put_contents($outputPath, $result ?? '');

        $message = "[OK] Auditoría simple ejecutada.\n";
        $message .= "[OK] Archivo generado: {$relativeOutput}\n";
        $message .= "[OK] Proyecto: {$this->projectRoot}\n\n";
        $message .= $result ?: '[OK] Comando ejecutado sin salida.';

        $this->log('LAB_AUDIT', $relativeOutput, $message);

        return $message;
    }

    private function applyEmbeddedPlainFile(string $content, string $mode): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", ltrim($content));
        $headerLine = trim($lines[0] ?? '');

        $style = null;

        if (preg_match(self::REGEX_PLAIN_FILE_HEADER_LINE, $headerLine, $matches)) {
            $style = 'line';
        } elseif (preg_match(self::REGEX_PLAIN_FILE_HEADER_BLOCK, $headerLine, $matches)) {
            $style = 'block';
        } else {
            return '[ERROR] No se encontró encabezado FILE válido para CSS/JS.';
        }

        $relativePath = trim($matches[1] ?? '');
        $version = trim($matches[2] ?? 'V1');

        if ($relativePath === '') {
            return '[ERROR] Ruta destino vacía.';
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if ($mode === 'css_full' && $extension !== 'css') {
            return "[ERROR] El modo CSS requiere archivo .css: {$relativePath}";
        }

        if ($mode === 'js_full' && $extension !== 'js') {
            return "[ERROR] El modo JS requiere archivo .js: {$relativePath}";
        }

        $targetPath = $this->projectRoot.'/'.$relativePath;
        $directory = dirname($targetPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        array_shift($lines);
        $body = ltrim(implode("\n", $lines), "\n");

        $header = $style === 'line'
            ? "// FILE: {$relativePath} | {$version}"
            : "/* FILE: {$relativePath} | {$version} */";

        $finalContent = $header."\n\n".$body;
        $status = file_exists($targetPath) ? 'sobrescrito' : 'creado';

        if (file_put_contents($targetPath, $finalContent) === false) {
            return "[ERROR] No se pudo escribir el archivo: {$relativePath}";
        }

        $message = "[OK] Modo: {$mode}\n";
        $message .= "[OK] Archivo: {$relativePath}\n";

        if ($status === 'sobrescrito') {
            $message .= "[WARN] Archivo existente reemplazado completamente.\n";
        } else {
            $message .= "[INFO] Archivo nuevo creado.\n";
        }

        $message .= "[OK] Estado: {$status}\n";
        $message .= "[OK] Versión: {$version}";

        $this->log('LAB_CODE', $relativePath, $message);

        $this->appendPipeLog(
            self::CODE_LOG_FILE,
            ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
            [
                $relativePath,
                date('Y-m-d H:i:s'),
                $status,
                $version,
                'project_lab',
                'archivo_completo',
                get_current_user() ?: 'unknown',
                php_uname('n') ?: 'unknown',
            ]
        );

        return $message;
    }

    private function runEmbeddedAssetSectionsTool(string $input): string
    {
        preg_match(self::REGEX_ASSET_SECTION_BLOCK, $input, $matches);

        $relativePath = trim($matches[1] ?? '');

        if ($relativePath === '') {
            return '[ERROR] Ruta destino vacía.';
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ASSET_SECTION_EXTENSIONS, true)) {
            return "[ERROR] Extensión no permitida para secciones: {$extension}";
        }

        $targetPath = $this->projectRoot.'/'.$relativePath;

        if (! file_exists($targetPath)) {
            return "[ERROR] Archivo no encontrado: {$relativePath}";
        }

        $content = file_get_contents($targetPath);

        if ($content === false) {
            return "[ERROR] No se pudo leer el archivo: {$relativePath}";
        }

        preg_match_all('/<<SECTION:\s*(.*?)\s*>>(.*?)<<END SECTION>>/su', $input, $sections, PREG_SET_ORDER);

        if (empty($sections)) {
            return '[ERROR] No se encontraron secciones para aplicar.';
        }

        $applied = 0;

        foreach ($sections as $section) {
            $sectionName = trim($section[1] ?? '');
            $sectionBody = trim($section[2] ?? '');

            if ($sectionName === '') {
                continue;
            }

            $escaped = preg_quote($sectionName, '/');

            if ($extension === 'css') {
                $pattern = '/\/\*\s*<<SECTION:\s*'.$escaped.'\s*>>\s*\*\/.*?\/\*\s*<<END SECTION>>\s*\*\//su';
                $replacement =
                    "/* <<SECTION: {$sectionName}>> */\n\n".
                    $sectionBody."\n\n".
                    '/* <<END SECTION>> */';
            } else {
                $pattern = '/\/\/\s*<<SECTION:\s*'.$escaped.'\s*>>.*?\/\/\s*<<END SECTION>>/su';
                $replacement =
                    "// <<SECTION: {$sectionName}>>\n\n".
                    $sectionBody."\n\n".
                    '// <<END SECTION>>';
            }

            $newContent = preg_replace($pattern, $replacement, $content, 1, $count);

            if ($newContent === null || $count === 0) {
                continue;
            }

            $content = $newContent;
            $applied++;
        }

        if ($applied === 0) {
            return '[ERROR] No se encontró ninguna sección destino en el archivo.';
        }

        if (file_put_contents($targetPath, $content) === false) {
            return "[ERROR] No se pudo escribir el archivo: {$relativePath}";
        }

        $message = "[OK] Modo: asset_sections\n";
        $message .= "[OK] Archivo: {$relativePath}\n";
        $message .= "[OK] Secciones aplicadas: {$applied}";

        $this->log('LAB_CODE', $relativePath, $message);

        $this->appendPipeLog(
            self::CODE_LOG_FILE,
            ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
            [
                $relativePath,
                date('Y-m-d H:i:s'),
                'actualizado',
                '-',
                'project_lab',
                'asset_sections',
                get_current_user() ?: 'unknown',
                php_uname('n') ?: 'unknown',
            ]
        );

        return $message;
    }


    private function findEmbeddedJsFunctionRange(string $content, string $functionName): ?array
    {
        $pattern = sprintf(
            self::REGEX_JS_FUNCTION_SIGNATURE_TEMPLATE,
            preg_quote($functionName, '/')
        );
    
        if (! preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }
    
        $start = $match[0][1];
    
        if (str_starts_with($match[0][0], "\n")) {
            $start++;
        }
    
        $openBrace = strpos($content, '{', $start);
    
        if ($openBrace === false) {
            return null;
        }
    
        $length = strlen($content);
        $depth = 0;
        $inString = null;
        $inTemplate = false;
        $inLineComment = false;
        $inBlockComment = false;
        $escaped = false;
    
        for ($i = $openBrace; $i < $length; $i++) {
            $char = $content[$i];
            $next = $content[$i + 1] ?? '';
    
            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }
    
                continue;
            }
    
            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
    
                continue;
            }
    
            if ($inString !== null) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
    
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
    
                if ($char === $inString) {
                    $inString = null;
                }
    
                continue;
            }
    
            if ($inTemplate) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
    
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
    
                if ($char === '`') {
                    $inTemplate = false;
                    continue;
                }
    
                continue;
            }
    
            if ($char === '/' && $next === '/') {
                $inLineComment = true;
                $i++;
                continue;
            }
    
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
    
            if ($char === '"' || $char === "'") {
                $inString = $char;
                continue;
            }
    
            if ($char === '`') {
                $inTemplate = true;
                continue;
            }
    
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
    
                if ($depth === 0) {
                    return [$start, $i + 1];
                }
            }
        }
    
        return null;
    }


    private function applyEmbeddedJsFunctionReplace(array $operation): string
    {
        $relativePath = $operation['path'];
        $functionName = $operation['method_name'];
        $functionCode = $operation['method_code'];
        $targetPath = $this->projectRoot.'/'.$relativePath;
    
        if (strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'js') {
            return "[ERROR] El reemplazo de función JS requiere archivo .js: {$relativePath}";
        }
    
        if (! file_exists($targetPath)) {
            return "[ERROR] El archivo destino no existe: {$relativePath}";
        }
    
        $content = file_get_contents($targetPath);
    
        if ($content === false) {
            return "[ERROR] No se pudo leer el archivo: {$relativePath}";
        }
    
        $range = $this->findEmbeddedJsFunctionRange($content, $functionName);
    
        if ($range === null) {
            return "[ERROR] No se encontró la función JS: {$functionName}";
        }
    
        [$start, $end] = $range;
    
        $newContent = substr($content, 0, $start)
            .rtrim($functionCode)
            .substr($content, $end);
    
        if (file_put_contents($targetPath, $newContent) === false) {
            return "[ERROR] No se pudo escribir el archivo: {$relativePath}";
        }
    
        $message = "[OK] Modo: js_function_patch\n";
        $message .= "[OK] Archivo: {$relativePath}\n";
        $message .= "[OK] Función JS reemplazada: {$functionName}";
    
        $this->log('LAB_CODE_JS_FUNCTION_REPLACE', "{$relativePath}::{$functionName}", $message);
    
        $this->appendPipeLog(
            self::CODE_LOG_FILE,
            ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
            [
                $relativePath,
                date('Y-m-d H:i:s'),
                'actualizado',
                '-',
                'project_lab',
                $functionName,
                get_current_user() ?: 'unknown',
                php_uname('n') ?: 'unknown',
            ]
        );
    
        return $message;
    }


    private function getAssetSectionsCatalog(): array
    {
        $catalog = [];
    
        $files = array_merge(
            glob($this->projectRoot.'/public/css/*.css') ?: [],
            glob($this->projectRoot.'/public/css/modules/*.css') ?: [],
            glob($this->projectRoot.'/public/js/*.js') ?: [],
            glob($this->projectRoot.'/tools/project-lab/assets/css/*.css') ?: [],
            glob($this->projectRoot.'/tools/project-lab/assets/js/*.js') ?: []
        );
    
        foreach ($files as $filePath) {
            if (! is_file($filePath)) {
                continue;
            }
    
            $relativePath = ltrim(str_replace($this->projectRoot, '', $filePath), '/');
            $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
    
            if (! in_array($extension, self::ASSET_SECTION_EXTENSIONS, true)) {
                continue;
            }
    
            $content = file_get_contents($filePath);
    
            if ($content === false) {
                continue;
            }
    
            preg_match_all(self::REGEX_SECTION_NAME, $content, $matches);
    
            $sections = array_values(array_unique(array_map('trim', $matches[1] ?? [])));
    
            if (empty($sections)) {
                continue;
            }
    
            $catalog[] = [
                'path' => $relativePath,
                'extension' => $extension,
                'sections' => $sections,
                'count' => count($sections),
            ];
        }
    
        usort($catalog, function (array $a, array $b): int {
            return strcmp($a['path'], $b['path']);
        });
    
        return $catalog;
    }
}
