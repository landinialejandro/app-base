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

    private const REGEX_TARGET_OPERATION = '/^\/\/\s*(?:TARGET|FILE):\s*(.+?)\s*(::|\+\+)\s*([a-zA-Z_][a-zA-Z0-9_]*)(?:\s*\|\s*V\d+)?\s*$/';

    private const REGEX_PHP_FILE_HEADER = '/^\/\/\s*FILE:\s*((?!.*(?:\s::\s|\s\+\+\s)).+?\.php)(?:\s*\|\s*(V\d+))?\s*$/';

    private const REGEX_BLADE_FILE_HEADER = '/^\{\{\-\-\s*FILE:\s*((?!.*(?:\s::\s|\s\+\+\s)).+?\.blade\.php)(?:\s*\|\s*(V\d+))?\s*\-\-\}\}$/';

    private const REGEX_PLAIN_FILE_HEADER_LINE = '/^\/\/\s*FILE:\s*((?!.*(?:\s::\s|\s\+\+\s)).+?\.(?:css|js))(?:\s*\|\s*(V\d+))?\s*$/';

    private const REGEX_PLAIN_FILE_HEADER_BLOCK = '/^\/\*\s*FILE:\s*((?!.*(?:\s::\s|\s\+\+\s)).+?\.(?:css|js))(?:\s*\|\s*(V\d+))?\s*\*\/$/';

    private const REGEX_ASSET_SECTION_BLOCK = '/REEMPLAZAR EN:\s*\[?(.+?\.(?:css|js))\]?\s*(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su';

    private const REGEX_SECTION_NAME = '/<<SECTION:\s*(.*?)\s*>>/su';

    private const REGEX_METHOD_SIGNATURE_TEMPLATE = '/^[ \t]*(?:public|protected|private)\s+(?:static\s+)?function\s+%s\s*\(/m';

    private const REGEX_JS_FUNCTION_SIGNATURE_TEMPLATE = '/(?:^|\n)(?:export\s+)?(?:async\s+)?function\s+%s\s*\(/';

    private $projectRoot;

    private $labRoot;

    private $logFile;

    private $csrfToken;

    private $rateLimitFile;

    private string $output = '';

    private string $code = '';

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
        'documentos/log',
        'documentos/auditoria',
        'documentos/baks',
        'storage/framework/cache',
    ];

    foreach ($dirs as $dir) {
        $result = $this->ensureProjectDirectory($dir);

        if ($result !== true) {
            throw new RuntimeException($result);
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

    private function checkRateLimit(): void
    {
        if (isset($_POST['ajax_rate_limit_reset'])) {
            return;
        }

        $limit = 300;
        $windowSeconds = 3600;

        $data = json_decode(@file_get_contents($this->rateLimitFile), true);

        if (! is_array($data)) {
            $data = [
                'count' => 0,
                'reset' => time() + $windowSeconds,
            ];
        }

        if (($data['reset'] ?? 0) < time()) {
            $data = [
                'count' => 0,
                'reset' => time() + $windowSeconds,
            ];
        }

        $data['count'] = (int) ($data['count'] ?? 0);
        $data['count']++;

        if ($data['count'] > $limit) {
            $remainingSeconds = max(0, ((int) ($data['reset'] ?? time())) - time());

            $this->jsonResponse([
                'ok' => false,
                'error' => 'Rate limit excedido',
                'output' => "[ERROR] Rate limit excedido.\n"
                    ."[INFO] Límite: {$limit} ejecuciones por hora.\n"
                    ."[INFO] Reinicio automático en {$remainingSeconds} segundos.\n"
                    .'[INFO] También puede reiniciarse manualmente desde Project Lab.',
                'rate_limit' => [
                    'limit' => $limit,
                    'count' => $data['count'],
                    'reset' => $data['reset'] ?? null,
                    'remaining_seconds' => $remainingSeconds,
                ],
            ], 429);
        }

        file_put_contents($this->rateLimitFile, json_encode($data));
    }

private function processActions()
{
    $output = '';
    $code = (string) ($_POST['code'] ?? '');

    if (isset($_POST['ajax_rate_limit_reset'])) {
        @unlink($this->rateLimitFile);

        $this->jsonResponse([
            'ok' => true,
            'output' => "[OK] Rate limit de Project Lab reiniciado.\n"
                ."[OK] Archivo temporal eliminado: {$this->rateLimitFile}",
        ]);
    }

    if (isset($_POST['ajax_save_console_audit'])) {
        $consoleOutput = (string) ($_POST['console_output'] ?? '');
        $output = $this->saveProjectConsoleAudit($consoleOutput);

        $this->jsonResponse([
            'ok' => true,
            'output' => $output,
        ]);
    }

    if (isset($_POST['ajax_lab_tool'])) {
        $labTool = (string) ($_POST['lab_tool'] ?? '');
        $fromClipboard = isset($_POST['from_clipboard']) && $_POST['from_clipboard'] === '1';

        $labInput = $fromClipboard
            ? $this->readClipboard()
            : (string) ($_POST['lab_input'] ?? '');

        if ($labTool === 'audit') {
            $quickCommand = $this->resolveQuickAuditCommand($labInput);

            if (($quickCommand['matched'] ?? false) && ! ($quickCommand['ok'] ?? false)) {
                $this->jsonResponse([
                    'ok' => false,
                    'tool' => $labTool,
                    'input' => $labInput,
                    'output' => $quickCommand['error'] ?? '[ERROR] Comando rápido Project Lab inválido.',
                ]);
            }

            if (($quickCommand['matched'] ?? false) && ($quickCommand['ok'] ?? false)) {
                $labInput = (string) ($quickCommand['input'] ?? $labInput);
            }
        }

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

    if (isset($_POST['ajax_tinker_from_clipboard'])) {
        $code = $this->readClipboard();

        if (trim($code) === '') {
            $this->jsonResponse([
                'ok' => false,
                'output' => '[ERROR] No se recibió código Tinker desde clipboard.',
                'code' => $code,
            ]);
        }

        $output = $this->executeTinker($code);

        $this->jsonResponse([
            'ok' => true,
            'output' => $output,
            'code' => $code,
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

    if (isset($_POST['run']) && trim($code) !== '') {
        $output = $this->executeTinker($code);
    }

    if (isset($_POST['artisan'])) {
        $output = $this->executeArtisan((string) $_POST['artisan']);
    }

    if (isset($_POST['lab_tool'])) {
        $labTool = (string) ($_POST['lab_tool'] ?? '');
        $fromClipboard = isset($_POST['from_clipboard']);

        $labInput = $fromClipboard
            ? $this->readClipboard()
            : (string) ($_POST['lab_input'] ?? '');

        if ($labTool === 'audit') {
            $quickCommand = $this->resolveQuickAuditCommand($labInput);

            if (($quickCommand['matched'] ?? false) && ! ($quickCommand['ok'] ?? false)) {
                $output = $quickCommand['error'] ?? '[ERROR] Comando rápido Project Lab inválido.';
            } elseif (($quickCommand['matched'] ?? false) && ($quickCommand['ok'] ?? false)) {
                $labInput = (string) ($quickCommand['input'] ?? $labInput);
                $output = $this->runAuditScript($labInput);
            } else {
                $output = $this->runAuditScript($labInput);
            }
        } elseif ($labTool === 'code') {
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

    $this->output = $output;
    $this->code = $code;
}

private function executeTinker($code)
{
    $code = str_replace(["\r\n", "\r"], "\n", trim((string) $code));

    if ($code === '') {
        return '[ERROR] No se recibió código Tinker.';
    }

    $artisanPath = $this->resolveProjectPath('artisan', false);

    if ($artisanPath === null || ! file_exists($artisanPath)) {
        return '[ERROR] No se encontró artisan dentro del root del proyecto actual.';
    }

    [$imports, $bodyCode] = $this->extractLeadingTinkerImports($code);

    if ($bodyCode === '') {
        return '[ERROR] El código Tinker no contiene cuerpo ejecutable luego de procesar imports.';
    }

    $wrappedCode = trim($imports)."\n\n"."
        \\DB::enableQueryLog();
        \$start = microtime(true);

        try {
            ob_start();

            \$result = (function() {
                {$bodyCode}
            })();

            \$buffer = trim(ob_get_clean());

            if (\$buffer !== '') {
                echo \$buffer;
            }

            if (isset(\$result)) {
                if (\$buffer !== '') {
                    echo \"\\n\";
                }

                echo \"--- RESULTADO ---\\n\";
                print_r(\$result);
            }
        } catch (\\Throwable \$e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            echo \"[ERROR] \" . get_class(\$e) . \"\\n\";
            echo \"[MENSAJE] \" . \$e->getMessage() . \"\\n\";
            echo \"[ARCHIVO] \" . \$e->getFile() . \"\\n\";
            echo \"[LÍNEA] \" . \$e->getLine() . \"\\n\";
        }

        \$queries = \\DB::getQueryLog();
        \$time = round((microtime(true) - \$start) * 1000, 2);

        echo \"\\n\\n--- SQL DEPURACIÓN (\" . count(\$queries) . \" consultas, {\$time}ms) ---\\n\";

        foreach (\$queries as \$q) {
            echo \"[\".\$q['time'].\"ms] \".\$q['query'].\" | Binds: \".json_encode(\$q['bindings']).\"\\n\";
        }
    ";

    $command = 'cd '.escapeshellarg($this->projectRoot)
        .' && php '.escapeshellarg($artisanPath).' tinker --execute='.escapeshellarg($wrappedCode);

    $output = shell_exec($command.' 2>&1');

    if (trim($imports) !== '') {
        $output = "[INFO] Imports Tinker detectados y ubicados fuera del wrapper.\n".$output;
    }

    $this->log('TINKER', $code, $output);
    $this->saveHistory($code, $output);

    return $output;
}

private function executeArtisan($command)
{
    $command = trim((string) $command);

    $allowedCommands = [
        'about',
        'db:seed',
        'migrate',
        'migrate:fresh --seed',
        'migrate:status',
        'optimize:clear',
        'queue:work --stop-when-empty --tries=1',
        'route:list',
    ];

    if (! in_array($command, $allowedCommands, true)) {
        $message = "[ERROR] Comando Artisan no permitido: {$command}";
        $this->log('ARTISAN_DENIED', $command, $message);

        return $message;
    }

    $artisanPath = $this->resolveProjectPath('artisan', false);

    if ($artisanPath === null || ! file_exists($artisanPath)) {
        return '[ERROR] No se encontró artisan dentro del root del proyecto actual.';
    }

    $parts = preg_split('/\s+/', $command);
    $parts = is_array($parts) ? array_values(array_filter($parts, 'strlen')) : [];

    if (empty($parts)) {
        return '[ERROR] Comando Artisan vacío.';
    }

    $escapedParts = array_map('escapeshellarg', $parts);

    $output = shell_exec(
        'cd '.escapeshellarg($this->projectRoot).
        ' && php '.escapeshellarg($artisanPath).' '.implode(' ', $escapedParts).' 2>&1'
    );

    $this->log('ARTISAN', $command, $output);

    return $output;
}

private function log($type, $command, $output)
{
    $timestamp = date('Y-m-d H:i:s');

    $content = "[{$timestamp}] {$type}: {$command}\n{$output}\n\n";

    $writeResult = $this->writeProjectFile(
        'documentos/log/project-lab.log',
        $content,
        true,
        true
    );

    if ($writeResult !== true) {
        error_log("[PROJECT_LAB_LOG_ERROR] {$writeResult}");
    }
}

private function saveHistory($code, $output)
{
    $historyRelativePath = 'storage/logs/tinker_history.json';
    $historyPath = $this->resolveProjectPath($historyRelativePath, true);

    if ($historyPath === null) {
        $this->log('HISTORY_ERROR', $historyRelativePath, '[ERROR] Ruta de historial fuera del proyecto o no permitida.');

        return;
    }

    $history = [];

    if (file_exists($historyPath)) {
        $history = json_decode(@file_get_contents($historyPath), true) ?? [];
    }

    array_unshift($history, [
        'code' => $code,
        'preview' => substr($code, 0, 50).(strlen($code) > 50 ? '...' : ''),
        'timestamp' => date('Y-m-d H:i:s'),
        'success' => strpos($output, 'ERROR:') === false,
    ]);

    $history = array_slice($history, 0, 15);

    $writeResult = $this->writeProjectFile(
        $historyRelativePath,
        json_encode($history),
        true
    );

    if ($writeResult !== true) {
        $this->log('HISTORY_ERROR', $historyRelativePath, $writeResult);
    }
}

private function getTablesInfo()
{
    $cacheRelativePath = 'storage/framework/cache/projectlab_tables.cache';
    $cacheFile = $this->resolveProjectPath($cacheRelativePath, true);

    if ($cacheFile !== null && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
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

        $this->writeProjectFile($cacheRelativePath, json_encode($tables), true);
    } catch (Exception $e) {
        // Silencio
    }

    return $tables;
}

private function getRoutes()
{
    $cacheRelativePath = 'storage/framework/cache/projectlab_routes.cache';
    $cacheFile = $this->resolveProjectPath($cacheRelativePath, true);

    if ($cacheFile !== null && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 600) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    $routes = [];

    try {
        Artisan::call('route:list', ['--json' => true]);
        $routes = json_decode(Artisan::output(), true) ?? [];

        $this->writeProjectFile($cacheRelativePath, json_encode($routes), true);
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
            'history' => $this->readProjectJsonFile('storage/logs/tinker_history.json', []),
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
        $path = $this->resolveProjectPath($folder, false);

        if ($path !== null && is_dir($path)) {
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
    $data = $this->readComposerLock();

    if ($data === []) {
        return [];
    }

    $packages = $data['packages'] ?? [];

    $featured = [
        'laravel/framework',
        'laravel/tinker',
        'laravel/fortify',
        'barryvdh/laravel-dompdf',
        'phpunit/phpunit',
    ];

    $installed = [];

    foreach ($packages as $pkg) {
        if (in_array($pkg['name'] ?? '', $featured, true)) {
            $installed[] = [
                'name' => $pkg['name'],
                'version' => $pkg['version'] ?? '-',
            ];
        }
    }

    return $installed;
}

private function getVendorCounts()
{
    $data = $this->readComposerLock();

    if ($data === []) {
        return [];
    }

    $vendors = [];

    foreach ($data['packages'] ?? [] as $pkg) {
        $name = $pkg['name'] ?? '';

        if (! str_contains($name, '/')) {
            continue;
        }

        [$vendor] = explode('/', $name, 2);

        $vendors[$vendor] = ($vendors[$vendor] ?? 0) + 1;
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
    $data = $this->readComposerLock();

    return count($data['packages'] ?? []);
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

    $targetPath = $this->resolveProjectPath($relativePath, true);

    if ($targetPath === null) {
        return "[ERROR] Ruta fuera del proyecto o no permitida: {$relativePath}";
    }

    array_shift($lines);
    $body = ltrim(implode("\n", $lines), "\n");

    $finalContent = "{{-- FILE: {$relativePath} | {$version} --}}\n\n".$body;
    $status = file_exists($targetPath) ? 'sobrescrito' : 'creado';

    $writeResult = $this->writeProjectFile($relativePath, $finalContent, true);

    if ($writeResult !== true) {
        return $writeResult;
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

    $targetPath = $this->resolveProjectPath($relativePath, true);

    if ($targetPath === null) {
        return "[ERROR] Ruta fuera del proyecto o no permitida: {$relativePath}";
    }

    $existedBefore = file_exists($targetPath);
    $previousContent = $existedBefore ? file_get_contents($targetPath) : null;

    if ($existedBefore && $previousContent === false) {
        return "[ERROR] No se pudo leer el archivo anterior: {$relativePath}";
    }

    array_shift($lines);

    $body = ltrim(implode("\n", $lines), "\n");

    $finalContent = "<?php\n\n// FILE: {$relativePath} | {$version}\n\n".$body;
    $status = $existedBefore ? 'sobrescrito' : 'creado';

    [$written, $lintResult] = $this->writePhpProjectFileWithLintRollback(
        $relativePath,
        $finalContent,
        true,
        is_string($previousContent) ? $previousContent : null,
        $existedBefore
    );

    if (! $written) {
        return $lintResult;
    }

    $message = "[OK] Modo: php_full\n";
    $message .= "[OK] Archivo: {$relativePath}\n";

    if ($status === 'sobrescrito') {
        $message .= "[WARN] Archivo existente reemplazado completamente.\n";
    } else {
        $message .= "[INFO] Archivo nuevo creado.\n";
    }

    $message .= "[OK] Estado: {$status}\n";
    $message .= "[OK] Versión: {$version}\n";
    $message .= $lintResult;

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
    $projectRoot = realpath($this->projectRoot);

    if ($projectRoot === false) {
        return '[ERROR] No se pudo resolver el root del proyecto.';
    }

    $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);

    $total = 0;
    $output = '';
    $lastSlug = '-';

    foreach ($matches as $match) {
        $slug = strtolower(trim($match[1]));
        $block = trim($match[2]);
        $lastSlug = $slug;

        if (! isset($documents[$slug])) {
            $output .= "[ERROR] Documento no reconocido por slug: {$slug}\n";

            continue;
        }

        if (! preg_match('/<<SECTION:\s*(.*?)\s*>>/su', $block, $sectionMatch)) {
            $output .= "[ERROR] No se pudo leer el nombre de sección para {$slug}\n";

            continue;
        }

        if (! preg_match('/\nSECTION_VERSION:\s*\d{5}\s*(?:\n|$)/u', "\n".$block)) {
            $output .= "[ERROR] [{$slug}] El bloque no incluye SECTION_VERSION válido de 5 dígitos.\n";

            continue;
        }

        $sectionName = trim($sectionMatch[1]);
        $filePath = $documents[$slug];
        $resolvedFilePath = realpath($filePath);

        if ($resolvedFilePath === false) {
            $output .= "[ERROR] No se pudo resolver documento: {$slug}\n";

            continue;
        }

        if (! str_starts_with($resolvedFilePath, $projectRoot.DIRECTORY_SEPARATOR)) {
            $output .= "[ERROR] Documento fuera del proyecto: {$slug}\n";

            continue;
        }

        $relativeDocPath = ltrim(substr($resolvedFilePath, strlen($projectRoot)), DIRECTORY_SEPARATOR);

        $content = file_get_contents($resolvedFilePath);

        if ($content === false) {
            $output .= "[ERROR] No se pudo leer documento: {$slug}\n";

            continue;
        }

        $pattern = '/<<SECTION:\s*'.preg_quote($sectionName, '/').'\s*>>.*?<<END SECTION>>/su';

        if (! preg_match($pattern, $content)) {
            $output .= "[ERROR] [{$slug}] No se encontró la sección: {$sectionName}\n";

            continue;
        }

        $timestamp = date('Ymd_His');
        $backupRelativePath = 'documentos/baks/'.pathinfo($resolvedFilePath, PATHINFO_FILENAME)."_{$timestamp}.bak";
        $backupResult = $this->writeProjectFile($backupRelativePath, $content, true);

        if ($backupResult !== true) {
            $output .= "[ERROR] [{$slug}] No se pudo guardar backup: {$backupResult}\n";

            continue;
        }

        $newContent = preg_replace($pattern, $block, $content, 1, $count);

        if ($count < 1 || $newContent === null) {
            $output .= "[ERROR] [{$slug}] No se pudo reemplazar la sección: {$sectionName}\n";

            continue;
        }

        $writeResult = $this->writeProjectFile($relativeDocPath, $newContent, false);

        if ($writeResult !== true) {
            $output .= "[ERROR] [{$slug}] No se pudo escribir el documento. {$writeResult}\n";

            continue;
        }

        $total++;
        $output .= "[OK] [{$slug}] Sección reemplazada: {$sectionName}\n";
        $output .= "[OK] [{$slug}] Backup: {$backupRelativePath}\n";
    }

    $output .= $total > 0
        ? "[OK] Proceso finalizado. Secciones aplicadas: {$total}"
        : '[WARN] Proceso finalizado sin cambios aplicados.';

    $this->log('LAB_DOCS', 'embedded-docs', $output);

    $this->appendPipeLog(
        'documentos/log/docs-updates.log',
        ['DOCUMENTO', 'FECHA', 'SECCIONES', 'USUARIO', 'HOST', 'ORIGEN'],
        [
            $lastSlug,
            date('Y-m-d H:i:s'),
            (string) $total,
            get_current_user() ?: 'unknown',
            php_uname('n') ?: 'unknown',
            'project_lab',
        ]
    );

    return $output;
}

private function indexEmbeddedDocuments(): array
{
    $baseDir = $this->resolveProjectPath('documentos', false);

    if ($baseDir === null || ! is_dir($baseDir)) {
        return [];
    }

    $documents = [];

    foreach (glob($baseDir.'/*.txt') ?: [] as $filePath) {
        $resolvedFilePath = realpath($filePath);

        if ($resolvedFilePath === false || ! is_file($resolvedFilePath)) {
            continue;
        }

        $content = file_get_contents($resolvedFilePath);

        if ($content === false) {
            continue;
        }

        if (preg_match('/DOC_SLUG:\s*([a-z0-9_]+)/', $content, $match)) {
            $documents[strtolower(trim($match[1]))] = $resolvedFilePath;
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

    $targetPath = $this->resolveProjectPath($relativePath, false);

    if ($targetPath === null) {
        return "[ERROR] Ruta fuera del proyecto o no permitida: {$relativePath}";
    }

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

    [$written, $lintResult] = $this->writePhpProjectFileWithLintRollback(
        $relativePath,
        $newContent,
        false,
        $content,
        true
    );

    if (! $written) {
        return $lintResult;
    }

    $message = "[OK] Modo: php_method_patch\n";
    $message .= "[OK] Archivo: {$relativePath}\n";
    $message .= "[OK] Método reemplazado: {$methodName}\n";
    $message .= $lintResult;

    $this->log('LAB_CODE_METHOD_REPLACE', "{$relativePath}::{$methodName}", $message);

    $this->appendPipeLog(
        self::CODE_LOG_FILE,
        ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
        [
            $relativePath,
            date('Y-m-d H:i:s'),
            'actualizado',
            '-',
            'project_lab',
            $methodName,
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

    $targetPath = $this->resolveProjectPath($relativePath, false);

    if ($targetPath === null) {
        return "[ERROR] Ruta fuera del proyecto o no permitida: {$relativePath}";
    }

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

    [$written, $lintResult] = $this->writePhpProjectFileWithLintRollback(
        $relativePath,
        $newContent,
        false,
        $content,
        true
    );

    if (! $written) {
        return $lintResult;
    }

    $message = "[OK] Modo: php_method_add\n";
    $message .= "[OK] Archivo: {$relativePath}\n";
    $message .= "[OK] Método agregado: {$methodName}\n";
    $message .= $lintResult;

    $this->log('LAB_CODE_METHOD_ADD', "{$relativePath}++{$methodName}", $message);

    $this->appendPipeLog(
        self::CODE_LOG_FILE,
        ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
        [
            $relativePath,
            date('Y-m-d H:i:s'),
            'actualizado',
            '-',
            'project_lab',
            $methodName,
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

private function appendPipeLog(string $relativeLogPath, array $columns, array $row): void
{
    $logPath = $this->resolveProjectPath($relativeLogPath, true);

    if ($logPath === null) {
        $this->log('LAB_PIPE_LOG_ERROR', $relativeLogPath, '[ERROR] Ruta de log fuera del proyecto o no permitida.');

        return;
    }

    $isNewFile = ! file_exists($logPath);

    $content = '';

    if ($isNewFile) {
        $content .= implode(' | ', $columns)."\n";
    }

    $content .= implode(' | ', $row)."\n";

    $writeResult = $this->writeProjectFile($relativeLogPath, $content, true, true);

    if ($writeResult !== true) {
        $this->log('LAB_PIPE_LOG_ERROR', $relativeLogPath, $writeResult);
    }
}

private function executeAuditScript(string $script): string
{
    $script = str_replace(["\r\n", "\r"], "\n", $script);

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
    $script = $this->normalizeScriptInput($script);

    if ($script === '') {
        return '[ERROR] No se recibió script de auditoría.';
    }

    $ensureAuditDirectory = $this->ensureAuditDirectory();

    if ($ensureAuditDirectory !== true) {
        return '[ERROR] No se pudo preparar el directorio de auditoría: '.$ensureAuditDirectory;
    }

    $inputPreview = $this->auditInputPreview($script);

    $hasExplicitAuditOutput = str_contains($script, 'documentos/auditoria/');

    if ($hasExplicitAuditOutput) {
        $result = $this->executeAuditScript($script);

        $this->log('AUDIT_SCRIPT', 'audit-inline', $result);

        return $result;
    }

    $timestamp = date('Ymd_His');
    $relativeOutput = "documentos/auditoria/auditoria_{$timestamp}.txt";

    $wrapped = "{\n";
    $wrapped .= "echo \"[OK] Auditoría simple ejecutada.\";\n";
    $wrapped .= "echo \"[OK] Archivo generado: {$relativeOutput}\";\n";
    $wrapped .= "echo \"[OK] Proyecto: {$this->projectRoot}\";\n";
    $wrapped .= "echo \"[INFO] Entrada: {$inputPreview}\";\n";
    $wrapped .= "echo \"\";\n";
    $wrapped .= $script."\n";
    $wrapped .= "} 2>&1 | tee ".escapeshellarg($relativeOutput);

    $result = $this->executeAuditScript($wrapped);

    $this->log('AUDIT_SCRIPT', $relativeOutput, $result);

    return $result;
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

    $targetPath = $this->resolveProjectPath($relativePath, true);

    if ($targetPath === null) {
        return "[ERROR] Ruta fuera del proyecto o no permitida: {$relativePath}";
    }

    array_shift($lines);
    $body = ltrim(implode("\n", $lines), "\n");

    $header = $style === 'line'
        ? "// FILE: {$relativePath} | {$version}"
        : "/* FILE: {$relativePath} | {$version} */";

    $finalContent = $header."\n\n".$body;
    $status = file_exists($targetPath) ? 'sobrescrito' : 'creado';

    $writeResult = $this->writeProjectFile($relativePath, $finalContent, true);

    if ($writeResult !== true) {
        return $writeResult;
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

    $targetPath = $this->resolveProjectPath($relativePath, false);

    if ($targetPath === null) {
        return "[ERROR] Ruta fuera del proyecto o no permitida: {$relativePath}";
    }

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
    $appliedSections = [];

    foreach ($sections as $section) {
        $sectionName = trim($section[1] ?? '');
        $sectionBody = $this->stripAssetSectionVersionFromBody($section[2] ?? '');

        if ($sectionName === '') {
            continue;
        }

        $escaped = preg_quote($sectionName, '/');

        if ($extension === 'css') {
            $pattern = '/\/\*\s*<<SECTION:\s*'.$escaped.'\s*>>\s*\*\/.*?\/\*\s*<<END SECTION>>\s*\*\//su';
        } else {
            $pattern = '/\/\/\s*<<SECTION:\s*'.$escaped.'\s*>>.*?\/\/\s*<<END SECTION>>/su';
        }

        if (! preg_match($pattern, $content, $targetMatches)) {
            continue;
        }

        $existingBlock = $targetMatches[0] ?? '';
        $nextVersion = $this->nextAssetSectionVersion($existingBlock);

        if ($extension === 'css') {
            $replacement =
                "/* <<SECTION: {$sectionName}>> */\n".
                "/* SECTION_VERSION: {$nextVersion} */\n\n".
                $sectionBody."\n\n".
                '/* <<END SECTION>> */';
        } else {
            $replacement =
                "// <<SECTION: {$sectionName}>>\n".
                "// SECTION_VERSION: {$nextVersion}\n\n".
                $sectionBody."\n\n".
                '// <<END SECTION>>';
        }

        $newContent = preg_replace($pattern, $replacement, $content, 1, $count);

        if ($newContent === null || $count === 0) {
            continue;
        }

        $content = $newContent;
        $applied++;
        $appliedSections[] = "{$sectionName}:{$nextVersion}";
    }

    if ($applied === 0) {
        return '[ERROR] No se encontró ninguna sección destino en el archivo.';
    }

    $writeResult = $this->writeProjectFile($relativePath, $content, false);

    if ($writeResult !== true) {
        return $writeResult;
    }

    $message = "[OK] Modo: asset_sections\n";
    $message .= "[OK] Archivo: {$relativePath}\n";
    $message .= "[OK] Secciones aplicadas: {$applied}\n";
    $message .= '[INFO] Versiones: '.implode(', ', $appliedSections);

    $this->log('LAB_CODE', $relativePath, $message);

    $this->appendPipeLog(
        self::CODE_LOG_FILE,
        ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
        [
            $relativePath,
            date('Y-m-d H:i:s'),
            'actualizado',
            implode(', ', $appliedSections),
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

    if (strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'js') {
        return "[ERROR] El reemplazo de función JS requiere archivo .js: {$relativePath}";
    }

    $targetPath = $this->resolveProjectPath($relativePath, false);

    if ($targetPath === null) {
        return "[ERROR] Ruta fuera del proyecto o no permitida: {$relativePath}";
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

    $writeResult = $this->writeProjectFile($relativePath, $newContent, false);

    if ($writeResult !== true) {
        return $writeResult;
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
    $projectRoot = realpath($this->projectRoot);

    if ($projectRoot === false) {
        return [];
    }

    $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);

    $catalog = [];

    $patterns = [
        'public/css/*.css',
        'public/css/modules/*.css',
        'public/js/*.js',
        'tools/project-lab/assets/css/*.css',
        'tools/project-lab/assets/js/*.js',
    ];

    $files = [];

    foreach ($patterns as $pattern) {
        $patternPath = $projectRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $pattern);
        $files = array_merge($files, glob($patternPath) ?: []);
    }

    foreach ($files as $filePath) {
        $resolvedFilePath = realpath($filePath);

        if ($resolvedFilePath === false || ! is_file($resolvedFilePath)) {
            continue;
        }

        if (! str_starts_with($resolvedFilePath, $projectRoot.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $relativePath = ltrim(substr($resolvedFilePath, strlen($projectRoot)), DIRECTORY_SEPARATOR);
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ASSET_SECTION_EXTENSIONS, true)) {
            continue;
        }

        $content = file_get_contents($resolvedFilePath);

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

    private function stripAssetSectionVersionFromBody(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        $body = preg_replace(
            '/^\s*(?:\/\*\s*)?SECTION_VERSION:\s*\d+(?:\s*\*\/)?\s*$/mi',
            '',
            $body
        );

        $body = preg_replace(
            '/^\s*\/\/\s*SECTION_VERSION:\s*\d+\s*$/mi',
            '',
            $body
        );

        return trim($body);
    }

    private function nextAssetSectionVersion(string $existingBlock): string
    {
        if (preg_match('/SECTION_VERSION:\s*(\d+)/i', $existingBlock, $matches)) {
            $current = (int) ($matches[1] ?? 0);

            return str_pad((string) ($current + 1), 5, '0', STR_PAD_LEFT);
        }

        return '00001';
    }

private function runPhpLint(string $relativePath): string
{
    if (strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'php') {
        return '';
    }

    $targetPath = $this->resolveProjectPath($relativePath, false);

    if ($targetPath === null) {
        return "[ERROR] Ruta fuera del proyecto o no permitida para lint PHP: {$relativePath}";
    }

    if (! file_exists($targetPath)) {
        return "[ERROR] Sintaxis PHP no verificada. Archivo no encontrado: {$relativePath}";
    }

    $command = 'cd '.escapeshellarg($this->projectRoot)
        .' && php -l '.escapeshellarg($targetPath).' 2>&1';

    $output = shell_exec($command);
    $output = trim((string) $output);

    if (str_contains($output, 'No syntax errors detected')) {
        return "[OK] Sintaxis PHP válida: {$relativePath}";
    }

    return "[ERROR] Sintaxis PHP inválida: {$relativePath}\n".$output;
}

private function saveProjectConsoleAudit(string $content): string
{
    $content = trim($content);

    if ($content === '') {
        return '[ERROR] No se recibió contenido de consola para guardar.';
    }

    $ensureAuditDirectory = $this->ensureAuditDirectory();

    if ($ensureAuditDirectory !== true) {
        return '[ERROR] No se pudo preparar el directorio de auditoría: '.$ensureAuditDirectory;
    }

    $timestamp = date('Ymd_His');
    $relativeOutput = "documentos/auditoria/consola_project_lab_{$timestamp}.txt";

    $header = "[OK] Consola Project Lab guardada como auditoría\n";
    $header .= "[OK] Archivo generado: {$relativeOutput}\n";
    $header .= "[OK] Proyecto: {$this->projectRoot}\n";
    $header .= "[OK] Fecha: ".date('Y-m-d H:i:s')."\n\n";

    $writeResult = $this->writeProjectFile($relativeOutput, $header.$content."\n", true);

    if ($writeResult !== true) {
        return '[ERROR] No se pudo guardar la consola como auditoría: '.$writeResult;
    }

    $output = $header.$content;

    $this->log('CONSOLE_AUDIT', $relativeOutput, $output);

    return $output;
}


    private function auditInputPreview(string $script): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($script));
        $normalized = is_string($normalized) ? trim($normalized) : trim($script);
    
        $normalized = str_replace('"', "'", $normalized);
    
        if (strlen($normalized) <= 110) {
            return $normalized;
        }
    
        return substr($normalized, 0, 50).' ... '.substr($normalized, -50);
    }


    private function resolveProjectPath(string $relativePath, bool $allowNewFile = false): ?string
    {
        $relativePath = str_replace(["\r\n", "\r", "\n", "\\"], ['', '', '', '/'], trim($relativePath));
    
        if ($relativePath === '') {
            return null;
        }
    
        if (str_starts_with($relativePath, '/')) {
            return null;
        }
    
        if (preg_match('#(^|/)\.\.(/|$)#', $relativePath)) {
            return null;
        }
    
        if (str_contains($relativePath, "\0")) {
            return null;
        }
    
        $projectRoot = realpath($this->projectRoot);
    
        if ($projectRoot === false) {
            return null;
        }
    
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
    
        $candidatePath = $projectRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    
        if (file_exists($candidatePath)) {
            $resolvedPath = realpath($candidatePath);
    
            if ($resolvedPath === false) {
                return null;
            }
    
            return str_starts_with($resolvedPath, $projectRoot.DIRECTORY_SEPARATOR) || $resolvedPath === $projectRoot
                ? $resolvedPath
                : null;
        }
    
        if (! $allowNewFile) {
            return null;
        }
    
        $parentDirectory = dirname($candidatePath);
    
        while (! is_dir($parentDirectory) && $parentDirectory !== dirname($parentDirectory)) {
            $parentDirectory = dirname($parentDirectory);
        }
    
        $resolvedParent = realpath($parentDirectory);
    
        if ($resolvedParent === false) {
            return null;
        }
    
        if (! str_starts_with($resolvedParent, $projectRoot.DIRECTORY_SEPARATOR) && $resolvedParent !== $projectRoot) {
            return null;
        }
    
        return $candidatePath;
    }


    private function writeProjectFile(string $relativePath, string $content, bool $allowNewFile = true, bool $append = false): true|string
    {
        $targetPath = $this->resolveProjectPath($relativePath, $allowNewFile);
    
        if ($targetPath === null) {
            return "[ERROR] Ruta fuera del proyecto o no permitida: {$relativePath}";
        }
    
        $directory = dirname($targetPath);
        $projectRoot = realpath($this->projectRoot);
    
        if ($projectRoot === false) {
            return '[ERROR] No se pudo resolver el root del proyecto.';
        }
    
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
    
        if (! is_dir($directory)) {
            $parent = dirname($directory);
    
            while (! is_dir($parent) && $parent !== dirname($parent)) {
                $parent = dirname($parent);
            }
    
            $resolvedParent = realpath($parent);
    
            if (
                $resolvedParent === false
                || (! str_starts_with($resolvedParent, $projectRoot.DIRECTORY_SEPARATOR) && $resolvedParent !== $projectRoot)
            ) {
                return "[ERROR] Directorio destino fuera del proyecto: {$relativePath}";
            }
    
            if (! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                return "[ERROR] No se pudo crear el directorio destino: {$relativePath}";
            }
        }
    
        $flags = $append ? FILE_APPEND : 0;
    
        if (file_put_contents($targetPath, $content, $flags) === false) {
            return "[ERROR] No se pudo escribir el archivo: {$relativePath}";
        }
    
        return true;
    }


    private function ensureProjectDirectory(string $relativePath): true|string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
    
        if ($relativePath === '') {
            return 'Ruta de directorio vacía.';
        }
    
        if (str_contains($relativePath, "\0")) {
            return 'Ruta de directorio inválida.';
        }
    
        if (str_starts_with($relativePath, '/') || preg_match('/^[A-Za-z]:\//', $relativePath)) {
            return 'No se permiten rutas absolutas.';
        }
    
        $segments = explode('/', $relativePath);
    
        if (in_array('..', $segments, true)) {
            return 'No se permiten rutas con segmentos ascendentes.';
        }
    
        $projectRoot = realpath($this->projectRoot);
    
        if ($projectRoot === false) {
            return 'No se pudo resolver el root del proyecto.';
        }
    
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $targetPath = $projectRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    
        if (! str_starts_with($targetPath, $projectRoot.DIRECTORY_SEPARATOR)) {
            return 'Directorio fuera del proyecto.';
        }
    
        if (is_dir($targetPath)) {
            return true;
        }
    
        if (! mkdir($targetPath, 0775, true) && ! is_dir($targetPath)) {
            return 'No se pudo crear directorio: '.$relativePath;
        }
    
        return true;
    }


private function writePhpProjectFileWithLintRollback(
    string $relativePath,
    string $newContent,
    bool $allowNewFile,
    ?string $previousContent,
    bool $existedBefore
): array {
    $writeResult = $this->writeProjectFile($relativePath, $newContent, $allowNewFile);

    if ($writeResult !== true) {
        return [false, $writeResult];
    }

    $lintResult = $this->runPhpLint($relativePath);

    if (str_starts_with($lintResult, '[OK]')) {
        return [true, $lintResult];
    }

    $lintDetail = "[INFO] Detalle lint:\n".$lintResult;

    if ($existedBefore && $previousContent !== null) {
        $rollbackResult = $this->writeProjectFile($relativePath, $previousContent, true);

        if ($rollbackResult !== true) {
            return [
                false,
                "[ERROR] Sintaxis PHP inválida y no se pudo restaurar el archivo anterior.\n"
                    .$lintDetail."\n"
                    ."[ERROR] Resultado rollback: ".$rollbackResult,
            ];
        }

        return [
            false,
            "[ERROR] Sintaxis PHP inválida. Se restauró el archivo anterior.\n".$lintDetail,
        ];
    }

    $targetPath = $this->resolveProjectPath($relativePath, false);

    if ($targetPath !== null && is_file($targetPath)) {
        @unlink($targetPath);
    }

    return [
        false,
        "[ERROR] Sintaxis PHP inválida. Se eliminó el archivo nuevo generado.\n".$lintDetail,
    ];
}


    private function readProjectJsonFile(string $relativePath, array $fallback = []): array
    {
        $targetPath = $this->resolveProjectPath($relativePath, false);
    
        if ($targetPath === null || ! is_file($targetPath)) {
            return $fallback;
        }
    
        $content = file_get_contents($targetPath);
    
        if ($content === false || trim($content) === '') {
            return $fallback;
        }
    
        $decoded = json_decode($content, true);
    
        return is_array($decoded) ? $decoded : $fallback;
    }


    private function readComposerLock(): array
    {
        return $this->readProjectJsonFile('composer.lock', []);
    }


    private function ensureAuditDirectory(): true|string
    {
        return $this->ensureProjectDirectory('documentos/auditoria');
    }


    private function normalizeScriptInput(string $script): string
    {
        $script = str_replace(["\r\n", "\r"], "\n", $script);
    
        return trim($script);
    }


    private function extractLeadingTinkerImports(string $code): array
    {
        $code = str_replace(["\r\n", "\r"], "\n", trim($code));
    
        if ($code === '') {
            return ['', ''];
        }
    
        $lines = explode("\n", $code);
        $imports = [];
        $body = [];
        $readingImports = true;
    
        foreach ($lines as $line) {
            $trimmed = trim($line);
    
            if ($readingImports && $trimmed === '') {
                continue;
            }
    
            if (
                $readingImports
                && preg_match('/^use\s+(function\s+|const\s+)?[A-Za-z_\\\\][A-Za-z0-9_\\\\]*(\s+as\s+[A-Za-z_][A-Za-z0-9_]*)?\s*;\s*$/', $trimmed)
            ) {
                $imports[] = $trimmed;
                continue;
            }
    
            $readingImports = false;
            $body[] = $line;
        }
    
        return [
            implode("\n", $imports),
            trim(implode("\n", $body)),
        ];
    }


private function resolveQuickAuditCommand(string $input): array
{
    $input = trim(str_replace(["\r\n", "\r"], "\n", $input));

    if (! str_starts_with($input, '>>')) {
        return [
            'matched' => false,
            'ok' => true,
            'input' => $input,
        ];
    }

    if (! preg_match('/^>>\s*find\s+(\S+)\s+(\S+)(?:\s+\+(\d+))?\s*$/i', $input, $matches)) {
        return [
            'matched' => true,
            'ok' => false,
            'input' => $input,
            'error' => implode("\n", [
                '[ERROR] Comando rápido Project Lab inválido.',
                '[INFO] Formato esperado:',
                '>> find <archivo|*> <termino> +<lineas>',
                '',
                '[INFO] Ejemplos:',
                '>> find * executeTinker +50',
                '>> find projectlab.php executeTinker +50',
            ]),
        ];
    }

    $filePattern = trim((string) ($matches[1] ?? ''));
    $term = trim((string) ($matches[2] ?? ''));
    $lines = isset($matches[3]) ? (int) $matches[3] : 30;

    if ($filePattern === '') {
        return [
            'matched' => true,
            'ok' => false,
            'input' => $input,
            'error' => '[ERROR] El patrón de archivo no puede estar vacío.',
        ];
    }

    if ($term === '' || $term === '*') {
        return [
            'matched' => true,
            'ok' => false,
            'input' => $input,
            'error' => "[ERROR] El término de búsqueda no puede estar vacío ni ser '*'.",
        ];
    }

    if ($lines < 1 || $lines > 300) {
        return [
            'matched' => true,
            'ok' => false,
            'input' => $input,
            'error' => '[ERROR] La cantidad de líneas debe estar entre 1 y 300.',
        ];
    }

    return [
        'matched' => true,
        'ok' => true,
        'input' => $this->buildQuickFindAuditScript($input, $filePattern, $term, $lines),
        'original_input' => $input,
        'command' => 'find',
    ];
}


private function buildQuickFindAuditScript(string $originalInput, string $filePattern, string $term, int $lines): string
{
    $template = $this->loadAuditMacroTemplate('find');

    if ($template === '') {
        return implode("\n", [
            'echo "[ERROR] Macro Project Lab no encontrada: find"',
            'echo "[INFO] Ruta esperada: tools/project-lab/macros/audit/find.sh.tpl"',
        ]);
    }

    return $this->renderAuditMacroTemplate($template, [
        'ORIGINAL_INPUT' => $this->escapeDoubleQuotedShellEcho($originalInput),
        'FILE_PATTERN' => $this->escapeDoubleQuotedShellEcho($filePattern),
        'TERM' => $this->escapeDoubleQuotedShellEcho($term),
        'LINES' => (string) $lines,
        'ORIGINAL_INPUT_SHELL' => escapeshellarg($originalInput),
        'FILE_PATTERN_SHELL' => escapeshellarg($filePattern),
        'TERM_SHELL' => escapeshellarg($term),
    ]);
}


    private function escapeDoubleQuotedShellEcho(string $value): string
    {
        return str_replace(
            ['\\', '"', '$', '`'],
            ['\\\\', '\"', '\$', '\`'],
            $value
        );
    }


    private function loadAuditMacroTemplate(string $macroName): string
    {
        if (! preg_match('/^[a-z0-9_-]+$/i', $macroName)) {
            return '';
        }
    
        $relativePath = "tools/project-lab/macros/audit/{$macroName}.sh.tpl";
        $targetPath = $this->resolveProjectPath($relativePath, false);
    
        if ($targetPath === null || ! is_file($targetPath)) {
            return '';
        }
    
        $content = file_get_contents($targetPath);
    
        return is_string($content) ? $content : '';
    }


    private function renderAuditMacroTemplate(string $template, array $values): string
    {
        foreach ($values as $key => $value) {
            $template = str_replace('{{'.$key.'}}', (string) $value, $template);
        }
    
        return $template;
    }
}
