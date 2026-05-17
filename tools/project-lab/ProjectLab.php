<?php

// FILE: tools/project-lab/ProjectLab.php |V1

/**
 * PROJECT LAB - Clase Principal
 * Maneja toda la lógica del dashboard
 */
class ProjectLab
{
    private const LAB_DOCUMENTS_DIR = 'tools/project-lab/documentos';

    private const LAB_LOG_DIR = self::LAB_DOCUMENTS_DIR.'/log';

    private const LAB_AUDIT_DIR = self::LAB_DOCUMENTS_DIR.'/auditoria';

    private const LAB_BACKUP_DIR = self::LAB_DOCUMENTS_DIR.'/baks';

    private const CODE_LOG_FILE = self::LAB_LOG_DIR.'/code-updates.log';

    private const ASSET_SECTION_EXTENSIONS = [
        'css',
        'js',
    ];

    private const LAB_PATCH_CODE_EXTENSIONS = [
        'php',
        'blade.php',
        'js',
        'css',
        'tpl',
        'sh',
        'md',
        'json',
        'xml',
        'yml',
        'yaml',
        'env.example',
    ];

    private const LAB_PATCH_FORBIDDEN_DOCUMENT_SUBDIRS = [
        'tools/project-lab/documentos/auditoria/',
        'tools/project-lab/documentos/baks/',
        'tools/project-lab/documentos/log/',
        'tools/project-lab/documentos/graficos/',
        'tools/project-lab/documentos/varios/',
    ];

    private const REGEX_TARGET_OPERATION = '/^\/\/\s*(?:TARGET|FILE):\s*(.+?)\s*(::|\+\+)\s*([a-zA-Z_][a-zA-Z0-9_]*)(?:\s*\|\s*V\d+)?\s*$/';

    private const REGEX_PHP_FILE_HEADER = '/^\/\/\s*FILE:\s*((?!.*(?:\s::\s|\s\+\+\s)).+?\.php)(?:\s*\|\s*(V\d+))?\s*$/';

    private const REGEX_BLADE_FILE_HEADER = '/^\{\{\-\-\s*FILE:\s*((?!.*(?:\s::\s|\s\+\+\s)).+?\.blade\.php)(?:\s*\|\s*(V\d+))?\s*\-\-\}\}$/';

    private const REGEX_PLAIN_FILE_HEADER_LINE = '/^(?:\/\/|#)\s*FILE:\s*((?!.*(?:\s::\s|\s\+\+\s)).+?\.(?:css|js|tpl))(?:\s*\|\s*(V\d+))?\s*$/';

    private const REGEX_PLAIN_FILE_HEADER_BLOCK = '/^\/\*\s*FILE:\s*((?!.*(?:\s::\s|\s\+\+\s)).+?\.(?:css|js|tpl))(?:\s*\|\s*(V\d+))?\s*\*\/$/';

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
        $this->logFile = $projectRoot.'/'.self::LAB_LOG_DIR.'/project-lab.log';
        $this->rateLimitFile = sys_get_temp_dir().'/projectlab_ratelimit.json';

        $this->ensureDirectories();
        $this->initSession();
    }

    private function ensureDirectories()
    {
        $dirs = [
            self::LAB_LOG_DIR,
            self::LAB_AUDIT_DIR,
            self::LAB_BACKUP_DIR,
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

        if (isset($_POST['ajax_ai_prompt']) || isset($_POST['ajax_ai_local'])) {
            $model = (string) ($_POST['model'] ?? '');
            $fromClipboard = isset($_POST['from_clipboard']) && $_POST['from_clipboard'] === '1';

            $editablePrompt = $fromClipboard
                ? $this->readClipboard()
                : (string) ($_POST['prompt'] ?? '');

            $consoleOutput = (string) ($_POST['console_output'] ?? '');

            if (isset($_POST['ajax_ai_local']) && ! str_contains($model, ':')) {
                $model = 'ollama:'.$model;
            }

            $prompt = $this->buildProjectLabAiUserPrompt($editablePrompt, $consoleOutput);
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            $aiOutput = $this->askAiPrompt($model, $prompt);

            $output = implode("\n\n", [
                $this->buildAiConsoleUserBlock($model, $fromClipboard, $editablePrompt, $consoleOutput),
                $this->buildAiConsoleAssistantBlock($aiOutput),
            ]);

            $this->jsonResponse([
                'ok' => ! str_starts_with($aiOutput, '[ERROR]'),
                'model' => $model,
                'from_clipboard' => $fromClipboard,
                'output' => $output,
            ]);
        }

        if (isset($_POST['ajax_document_audit'])) {
            $model = (string) ($_POST['model'] ?? '');
            $fragments = (string) ($_POST['fragments'] ?? '');
            $selectedSlug = (string) ($_POST['selected_doc_slug'] ?? '');
            $selectedSection = (string) ($_POST['selected_section_name'] ?? '');
            $consoleOutput = (string) ($_POST['console_output'] ?? '');

            $documents = $this->getTechnicalDocumentsForLab();
            $prompt = $this->buildDocumentAuditPrompt($fragments, $selectedSlug, $selectedSection, $documents, $consoleOutput);

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $aiOutput = $this->askAiPrompt($model, $prompt);

            $output = implode("\n\n", [
                $this->buildAiConsoleUserBlock($model, false, $prompt, $consoleOutput),
                $this->buildAiConsoleAssistantBlock($aiOutput),
            ]);

            $this->jsonResponse([
                'ok' => ! str_starts_with($aiOutput, '[ERROR]'),
                'model' => $model,
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
                'ok' => ! str_starts_with($output, '[ERROR]'),
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
            self::LAB_LOG_DIR.'/project-lab.log',
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
            'labToolInput' => '',
            'labToolOutput' => '',
            'labToolActive' => 'code',
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
            'technicalDocuments' => $this->getTechnicalDocumentsForLab(),
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

    public function runLabPatchTool(string $input): string
    {
        $input = str_replace(["\r\n", "\r"], "\n", trim($input));

        if ($input === '') {
            return '[ERROR] No se recibió contenido LAB_PATCH.';
        }

        $blocks = $this->parseLabPatchBlocks($input);

        if (isset($blocks['error'])) {
            return $blocks['error'];
        }

        $patches = $blocks['patches'] ?? [];
        $total = count($patches);

        if ($total < 1) {
            return '[ERROR] No se encontraron bloques LAB_PATCH.';
        }

        $output = "[OK] Modo: lab_patch\n";

        foreach ($patches as $index => $patch) {
            $patchNumber = $index + 1;
            $result = $this->applyLabPatch($patch, $patchNumber, $total);
            $output .= $result['output'];

            if (! ($result['ok'] ?? false)) {
                $output .= "[ERROR] Proceso detenido en patch {$patchNumber}/{$total}. No se aplican patches siguientes.";
                $this->log('LAB_PATCH_ERROR', $patch['file'] ?? 'sin-archivo', $output);

                return $output;
            }
        }

        $output .= "[OK] Proceso finalizado. Patches aplicados: {$total}";
        $this->log('LAB_PATCH', 'lab-patch', $output);

        return $output;
    }

    private function parseLabPatchBlocks(string $input): array
    {
        preg_match_all('/(?:^|\n)LAB_PATCH\s*\n(.*?)\nEND_LAB_PATCH(?=\n|$)/su', $input, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return ['error' => '[ERROR] No se encontraron bloques LAB_PATCH ... END_LAB_PATCH.'];
        }

        $patches = [];

        foreach ($matches as $index => $match) {
            $patchNumber = $index + 1;
            $block = $match[1] ?? '';
            $anchorMarker = "\nANCHOR:\n";
            $textMarker = "\nTEXT:\n";
            $anchorPosition = strpos("\n".$block, $anchorMarker);

            if ($anchorPosition === false) {
                return ['error' => "[ERROR] Patch {$patchNumber}: falta bloque ANCHOR."];
            }

            $header = substr("\n".$block, 0, $anchorPosition);
            $afterAnchor = substr("\n".$block, $anchorPosition + strlen($anchorMarker));
            $textPosition = strpos($afterAnchor, $textMarker);

            if ($textPosition === false) {
                return ['error' => "[ERROR] Patch {$patchNumber}: falta bloque TEXT."];
            }

            $anchor = substr($afterAnchor, 0, $textPosition);
            $text = substr($afterAnchor, $textPosition + strlen($textMarker));
            $fields = [];

            foreach (explode("\n", trim($header)) as $line) {
                if (trim($line) === '') {
                    continue;
                }

                if (! preg_match('/^([A-Z_]+):\s*(.*)$/', $line, $fieldMatch)) {
                    return ['error' => "[ERROR] Patch {$patchNumber}: línea de campo inválida: {$line}"];
                }

                $fieldName = strtoupper(trim($fieldMatch[1]));

                if (isset($fields[$fieldName])) {
                    return ['error' => "[ERROR] Patch {$patchNumber}: campo duplicado: {$fieldName}"];
                }

                $fields[$fieldName] = trim($fieldMatch[2]);
            }

            foreach (['FILE', 'OP'] as $requiredField) {
                if (! array_key_exists($requiredField, $fields) || $fields[$requiredField] === '') {
                    return ['error' => "[ERROR] Patch {$patchNumber}: falta campo obligatorio {$requiredField}."];
                }
            }

            if ($anchor === '') {
                return ['error' => "[ERROR] Patch {$patchNumber}: ANCHOR no puede estar vacío."];
            }

            $patches[] = [
                'file' => $fields['FILE'],
                'type' => strtolower($fields['TYPE'] ?? ''),
                'op' => strtolower($fields['OP']),
                'position' => strtolower($fields['POSITION'] ?? ''),
                'match' => strtolower($fields['MATCH'] ?? 'one'),
                'anchor' => $anchor,
                'text' => $text,
            ];
        }

        return ['patches' => $patches];
    }

    private function applyLabPatch(array $patch, int $patchNumber, int $total): array
    {
        $file = (string) ($patch['file'] ?? '');
        $typeResult = $this->inferLabPatchType($file, (string) ($patch['type'] ?? ''));

        if (! ($typeResult['ok'] ?? false)) {
            return [
                'ok' => false,
                'output' => "[OK] Patch: {$patchNumber}/{$total}\n[ERROR] Archivo: {$file}\n".$typeResult['error']."\n",
            ];
        }

        $patch['type'] = $typeResult['type'];

        $validation = $this->validateLabPatchTarget($patch);

        if (! ($validation['ok'] ?? false)) {
            return [
                'ok' => false,
                'output' => "[OK] Patch: {$patchNumber}/{$total}\n[ERROR] Archivo: {$file}\n".$validation['error']."\n",
            ];
        }

        return $patch['type'] === 'docs'
            ? $this->applyLabPatchDocs($patch, $patchNumber, $total)
            : $this->applyLabPatchCode($patch, $patchNumber, $total);
    }

    private function inferLabPatchType(string $relativePath, string $declaredType): array
    {
        $normalizedPath = str_replace('\\', '/', trim($relativePath));
        $inferredType = null;

        if (str_starts_with($normalizedPath, self::LAB_DOCUMENTS_DIR.'/') && str_ends_with($normalizedPath, '.txt')) {
            $inferredType = 'docs';
        } elseif (in_array($this->labPatchExtension($normalizedPath), self::LAB_PATCH_CODE_EXTENSIONS, true)) {
            $inferredType = 'code';
        }

        if ($declaredType !== '' && ! in_array($declaredType, ['code', 'docs'], true)) {
            return ['ok' => false, 'error' => "[ERROR] TYPE inválido: {$declaredType}"];
        }

        if ($inferredType === null) {
            return ['ok' => false, 'error' => "[ERROR] No se pudo inferir TYPE para: {$relativePath}"];
        }

        if ($declaredType !== '' && $declaredType !== $inferredType) {
            return ['ok' => false, 'error' => "[ERROR] TYPE declarado no coincide con ruta/extensión permitida. Declarado: {$declaredType}. Inferido: {$inferredType}"];
        }

        return ['ok' => true, 'type' => $inferredType];
    }

    private function validateLabPatchTarget(array $patch): array
    {
        $file = (string) ($patch['file'] ?? '');
        $op = (string) ($patch['op'] ?? '');
        $position = (string) ($patch['position'] ?? '');
        $match = (string) ($patch['match'] ?? 'one');

        if (! in_array($op, ['insert', 'replace'], true)) {
            return ['ok' => false, 'error' => "[ERROR] OP inválido: {$op}"];
        }

        if (! in_array($match, ['one', 'all'], true)) {
            return ['ok' => false, 'error' => "[ERROR] MATCH inválido: {$match}"];
        }

        if ($match === 'all' && $op === 'insert') {
            return ['ok' => false, 'error' => '[ERROR] MATCH=all con OP=insert no está permitido.'];
        }

        if ($op === 'insert' && ! in_array($position, ['before', 'after'], true)) {
            return ['ok' => false, 'error' => '[ERROR] POSITION debe ser before o after cuando OP=insert.'];
        }

        if ($op === 'replace' && $position !== '') {
            return ['ok' => false, 'error' => '[ERROR] POSITION no aplica con OP=replace.'];
        }

        if ($file === '' || str_starts_with($file, '/') || preg_match('#(^|/)\.\.(/|$)#', $file) || str_contains($file, "\0")) {
            return ['ok' => false, 'error' => "[ERROR] Ruta inválida para LAB_PATCH: {$file}"];
        }

        $normalizedPath = str_replace('\\', '/', $file);

        foreach (self::LAB_PATCH_FORBIDDEN_DOCUMENT_SUBDIRS as $forbiddenDir) {
            if (str_starts_with($normalizedPath, $forbiddenDir)) {
                return ['ok' => false, 'error' => "[ERROR] LAB_PATCH no puede modificar carpeta protegida: {$forbiddenDir}"];
            }
        }

        if (($patch['type'] ?? '') === 'docs') {
            if (! preg_match('#^tools/project-lab/documentos/[^/]+\.txt$#', $normalizedPath)) {
                return ['ok' => false, 'error' => '[ERROR] TYPE=docs solo permite tools/project-lab/documentos/*.txt'];
            }
        }

        return ['ok' => true];
    }

    private function applyLabPatchDocs(array $patch, int $patchNumber, int $total): array
    {
        $file = $patch['file'];
        $targetPath = $this->resolveProjectPath($file, false);

        if ($targetPath === null || ! is_file($targetPath)) {
            return ['ok' => false, 'output' => "[OK] Patch: {$patchNumber}/{$total}\n[ERROR] Archivo: {$file}\n[ERROR] Archivo docs no existe.\n"];
        }

        $content = file_get_contents($targetPath);

        if ($content === false) {
            return ['ok' => false, 'output' => "[OK] Patch: {$patchNumber}/{$total}\n[ERROR] Archivo: {$file}\n[ERROR] No se pudo leer archivo docs.\n"];
        }

        $balanceBefore = $this->validateDocSectionsBalanced($content);

        if ($balanceBefore !== true) {
            return ['ok' => false, 'output' => "[OK] Patch: {$patchNumber}/{$total}\n[ERROR] Archivo: {$file}\n[ERROR] Documento inválido antes del patch: {$balanceBefore}\n"];
        }

        if (! preg_match('/^DOC_SLUG:\s*([a-z0-9_]+)\s*$/mu', $content, $slugMatch)) {
            return ['ok' => false, 'output' => "[OK] Patch: {$patchNumber}/{$total}\n[ERROR] Archivo: {$file}\n[ERROR] El documento no contiene DOC_SLUG válido.\n"];
        }

        $docSlugLine = $slugMatch[0];
        $applyResult = $this->applyTextPatch($content, $patch);

        if (! ($applyResult['ok'] ?? false)) {
            return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, $applyResult['error'] ?? '[ERROR] No se pudo aplicar patch.')];
        }

        $affectedSections = [];

        foreach ($applyResult['offsets'] as $offset) {
            $section = $this->detectDocSectionForOffset($content, $offset);

            if ($section !== null) {
                $affectedSections[$section['name']] = $section;
            }
        }

        $newContent = $applyResult['content'];

        if (! preg_match('/^DOC_SLUG:\s*([a-z0-9_]+)\s*$/mu', $newContent, $newSlugMatch) || $newSlugMatch[0] !== $docSlugLine) {
            return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, '[ERROR] LAB_PATCH docs no puede modificar DOC_SLUG.')];
        }

        $sectionVersionLines = [];

        foreach ($affectedSections as $sectionName => $section) {
            $versionResult = $this->incrementDocSectionVersion($newContent, $sectionName);

            if (! ($versionResult['ok'] ?? false)) {
                return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, $versionResult['error'] ?? '[ERROR] No se pudo actualizar SECTION_VERSION.')];
            }

            $newContent = $versionResult['content'];
            $sectionVersionLines[] = "[OK] SECTION_VERSION actualizado: {$sectionName} {$versionResult['old']} -> {$versionResult['new']}";
        }

        $balanceAfter = $this->validateDocSectionsBalanced($newContent);

        if ($balanceAfter !== true) {
            return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, "[ERROR] Documento inválido después del patch: {$balanceAfter}")];
        }

        $timestamp = date('Ymd_His');
        $backupRelativePath = self::LAB_BACKUP_DIR.'/'.pathinfo($targetPath, PATHINFO_FILENAME)."_lab_patch_{$timestamp}.bak";
        $backupResult = $this->writeProjectFile($backupRelativePath, $content, true);

        if ($backupResult !== true) {
            return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, "[ERROR] No se pudo guardar backup: {$backupResult}")];
        }

        $writeResult = $this->writeProjectFile($file, $newContent, false);

        if ($writeResult !== true) {
            return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, "[ERROR] No se pudo escribir documento: {$writeResult}")];
        }

        $output = $this->formatLabPatchOkHeader($patch, $patchNumber, $total, $applyResult['count']);
        $output .= "[OK] Backup: {$backupRelativePath}\n";
        $output .= empty($sectionVersionLines)
            ? "[WARN] SECTION_VERSION no actualizado: ANCHOR fuera de sección detectada.\n"
            : implode("\n", $sectionVersionLines)."\n";
        $output .= "[OK] Validación: DOC_SLUG y balance de secciones válidos.\n";

        return ['ok' => true, 'output' => $output];
    }

    private function applyLabPatchCode(array $patch, int $patchNumber, int $total): array
    {
        $file = $patch['file'];
        $targetPath = $this->resolveProjectPath($file, false);

        if ($targetPath === null || ! is_file($targetPath)) {
            return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, '[ERROR] Archivo code no existe o está fuera del proyecto.')];
        }

        $content = file_get_contents($targetPath);

        if ($content === false) {
            return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, '[ERROR] No se pudo leer archivo code.')];
        }

        $applyResult = $this->applyTextPatch($content, $patch);

        if (! ($applyResult['ok'] ?? false)) {
            return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, $applyResult['error'] ?? '[ERROR] No se pudo aplicar patch.')];
        }

        $writeResult = $this->writeProjectFile($file, $applyResult['content'], false);

        if ($writeResult !== true) {
            return ['ok' => false, 'output' => $this->formatLabPatchErrorOutput($patch, $patchNumber, $total, "[ERROR] No se pudo escribir archivo code: {$writeResult}")];
        }

        $validation = $this->validateCodePatchResult($file);
        $rolledBack = false;

        if (! ($validation['ok'] ?? false)) {
            $rollbackResult = $this->writeProjectFile($file, $content, false);
            $rolledBack = $rollbackResult === true;
            $output = $this->formatLabPatchErrorOutput(
                $patch,
                $patchNumber,
                $total,
                ($validation['output'] ?? '[ERROR] Validación code falló.')."\n".($rolledBack ? '[OK] Rollback: contenido anterior restaurado.' : "[ERROR] Rollback falló: {$rollbackResult}")
            );

            return ['ok' => false, 'output' => $output];
        }

        $output = $this->formatLabPatchOkHeader($patch, $patchNumber, $total, $applyResult['count']);
        $output .= '[OK] Validación: '.$validation['output']."\n";
        $output .= '[OK] Rollback: no requerido'."\n";

        return ['ok' => true, 'output' => $output];
    }

    private function applyTextPatch(string $content, array $patch): array
    {
        $anchor = $patch['anchor'];
        $text = $patch['text'];
        $op = $patch['op'];
        $match = $patch['match'];
        $count = $this->countExactOccurrences($content, $anchor);

        if ($count < 1) {
            return ['ok' => false, 'error' => '[ERROR] ANCHOR no encontrado.'];
        }

        if ($match === 'one' && $count > 1) {
            return ['ok' => false, 'error' => "[ERROR] ANCHOR ambiguo: ocurrencias encontradas {$count} con MATCH=one."];
        }

        if ($op === 'insert') {
            $offset = strpos($content, $anchor);

            if ($offset === false) {
                return ['ok' => false, 'error' => '[ERROR] ANCHOR no encontrado.'];
            }

            $insertOffset = $patch['position'] === 'before' ? $offset : $offset + strlen($anchor);
            $newContent = substr($content, 0, $insertOffset).$text.substr($content, $insertOffset);

            return ['ok' => true, 'content' => $newContent, 'count' => 1, 'offsets' => [$offset]];
        }

        $offsets = [];
        $searchOffset = 0;

        while (($offset = strpos($content, $anchor, $searchOffset)) !== false) {
            $offsets[] = $offset;
            $searchOffset = $offset + strlen($anchor);

            if ($match === 'one') {
                break;
            }
        }

        if ($match === 'all') {
            $newContent = str_replace($anchor, $text, $content, $affected);

            return ['ok' => true, 'content' => $newContent, 'count' => $affected, 'offsets' => $offsets];
        }

        $offset = $offsets[0] ?? null;

        if ($offset === null) {
            return ['ok' => false, 'error' => '[ERROR] ANCHOR no encontrado.'];
        }

        $newContent = substr($content, 0, $offset).$text.substr($content, $offset + strlen($anchor));

        return ['ok' => true, 'content' => $newContent, 'count' => 1, 'offsets' => [$offset]];
    }

    private function countExactOccurrences(string $content, string $anchor): int
    {
        return $anchor === '' ? 0 : substr_count($content, $anchor);
    }

    private function detectDocSectionForOffset(string $content, int $offset): ?array
    {
        preg_match_all('/<<SECTION:\s*(.*?)\s*>>.*?<<END SECTION>>/su', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            $block = $match[0][0] ?? '';
            $start = $match[0][1] ?? null;
            $name = trim($match[1][0] ?? '');

            if ($start === null || $name === '') {
                continue;
            }

            $end = $start + strlen($block);

            if ($offset >= $start && $offset < $end) {
                return [
                    'name' => $name,
                    'start' => $start,
                    'end' => $end,
                    'block' => $block,
                ];
            }
        }

        return null;
    }

    private function incrementDocSectionVersion(string $content, string $sectionName): array
    {
        $pattern = '/(<<SECTION:\s*'.preg_quote($sectionName, '/').'\s*>>.*?)(SECTION_VERSION:\s*)(\d{5})(.*?<<END SECTION>>)/su';
        $count = preg_match_all($pattern, $content);

        if ($count < 1) {
            return ['ok' => false, 'error' => "[ERROR] No se encontró SECTION_VERSION para sección afectada: {$sectionName}"];
        }

        if ($count > 1) {
            return ['ok' => false, 'error' => "[ERROR] Sección ambigua al actualizar SECTION_VERSION: {$sectionName}"];
        }

        $oldVersion = null;
        $newVersion = null;
        $newContent = preg_replace_callback(
            $pattern,
            function (array $matches) use (&$oldVersion, &$newVersion): string {
                $oldVersion = $matches[3];
                $newVersion = str_pad((string) (((int) $oldVersion) + 1), 5, '0', STR_PAD_LEFT);

                return $matches[1].$matches[2].$newVersion.$matches[4];
            },
            $content,
            1,
            $replaceCount
        );

        if ($newContent === null || $replaceCount !== 1 || $oldVersion === null || $newVersion === null) {
            return ['ok' => false, 'error' => "[ERROR] No se pudo actualizar SECTION_VERSION: {$sectionName}"];
        }

        return [
            'ok' => true,
            'content' => $newContent,
            'old' => $oldVersion,
            'new' => $newVersion,
        ];
    }

    private function validateDocSectionsBalanced(string $content): true|string
    {
        preg_match_all('/<<SECTION:\s*.*?>>|<<END SECTION>>/su', $content, $matches);

        $depth = 0;

        foreach ($matches[0] ?? [] as $token) {
            if (str_starts_with($token, '<<SECTION:')) {
                if ($depth !== 0) {
                    return 'sección anidada o delimitador <<END SECTION>> faltante.';
                }

                $depth++;
            } else {
                if ($depth !== 1) {
                    return 'delimitador <<END SECTION>> sin apertura.';
                }

                $depth--;
            }
        }

        return $depth === 0 ? true : 'delimitador <<END SECTION>> faltante.';
    }

    private function validateCodePatchResult(string $relativePath): array
    {
        $extension = $this->labPatchExtension($relativePath);

        if ($extension === 'php') {
            $lintResult = $this->runPhpLint($relativePath);

            return str_starts_with($lintResult, '[OK]')
                ? ['ok' => true, 'output' => $lintResult]
                : ['ok' => false, 'output' => $lintResult];
        }

        if ($extension === 'js') {
            $targetPath = $this->resolveProjectPath($relativePath, false);
            $nodePath = trim((string) shell_exec('command -v node 2>/dev/null'));

            if ($nodePath === '' || $targetPath === null) {
                return ['ok' => true, 'output' => 'node --check omitido: node no disponible.'];
            }

            $command = 'cd '.escapeshellarg($this->projectRoot)
                .' && node --check '.escapeshellarg($targetPath).' 2>&1';
            $output = trim((string) shell_exec($command));

            return str_contains($output, 'SyntaxError')
                ? ['ok' => false, 'output' => "[ERROR] node --check falló: {$relativePath}\n".$output]
                : ['ok' => true, 'output' => 'node --check OK: '.$relativePath.($output !== '' ? "\n".$output : '')];
        }

        if ($extension === 'json') {
            $targetPath = $this->resolveProjectPath($relativePath, false);
            $content = $targetPath !== null ? file_get_contents($targetPath) : false;

            if ($content === false) {
                return ['ok' => false, 'output' => "[ERROR] No se pudo leer JSON para validar: {$relativePath}"];
            }

            json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['ok' => false, 'output' => '[ERROR] json_decode falló: '.json_last_error_msg()];
            }

            return ['ok' => true, 'output' => 'json_decode OK: '.$relativePath];
        }

        return ['ok' => true, 'output' => 'escritura verificada: '.$relativePath];
    }

    private function labPatchExtension(string $relativePath): string
    {
        $path = strtolower(str_replace('\\', '/', trim($relativePath)));

        foreach (['blade.php', 'env.example'] as $compoundExtension) {
            if (str_ends_with($path, '.'.$compoundExtension)) {
                return $compoundExtension;
            }
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    private function formatLabPatchOkHeader(array $patch, int $patchNumber, int $total, int $affected): string
    {
        return implode("\n", [
            "[OK] Patch: {$patchNumber}/{$total}",
            '[OK] Archivo: '.$patch['file'],
            '[OK] Tipo: '.$patch['type'],
            '[OK] Operación: '.$patch['op'],
            '[OK] Match: '.$patch['match'],
            "[OK] Ocurrencias afectadas: {$affected}",
        ])."\n";
    }

    private function formatLabPatchErrorOutput(array $patch, int $patchNumber, int $total, string $error): string
    {
        return implode("\n", [
            "[OK] Patch: {$patchNumber}/{$total}",
            '[ERROR] Archivo: '.($patch['file'] ?? '-'),
            '[ERROR] Tipo: '.($patch['type'] ?? '-'),
            '[ERROR] Operación: '.($patch['op'] ?? '-'),
            '[ERROR] Match: '.($patch['match'] ?? '-'),
            rtrim($error),
        ])."\n";
    }

    private function runEmbeddedCodeTool(string $input): string
    {
        $input = str_replace(["\r\n", "\r"], "\n", trim($input));

        if ($input === '') {
            return '[ERROR] No se recibió contenido para actualizar código.';
        }

        if (str_contains($input, 'LAB_PATCH')) {
            return $this->runLabPatchTool($input);
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

        if (
            preg_match('/^\s*#\s*FILE:\s*.+?\.tpl(?:\s*\|\s*V\d+)?\s*$/m', $input)
            || preg_match('/^\s*\/\/\s*FILE:\s*.+?\.tpl(?:\s*\|\s*V\d+)?\s*$/m', $input)
            || preg_match('/^\s*\/\*\s*FILE:\s*.+?\.tpl(?:\s*\|\s*V\d+)?\s*\*\//', $input)
        ) {
            return $this->applyEmbeddedPlainFile($input, 'tpl_full');
        }

        return "[ERROR] Formato no compatible en herramienta de código.\n[INFO] Soporta PHP completo, Blade completo, CSS completo, JS completo, TPL completo, secciones CSS/JS, TARGET :: método PHP, TARGET ++ método PHP y TARGET :: función JS.";
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

        if (str_contains($input, 'LAB_PATCH')) {
            return $this->runLabPatchTool($input);
        }

        $replaceMatches = [];
        $addMatches = [];

        preg_match_all(
            '/REEMPLAZAR EN:\s*\[?([a-z0-9_]+)\]?\s*(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su',
            $input,
            $replaceMatches,
            PREG_SET_ORDER
        );

        preg_match_all(
            '/(?:AGREGAR EN|NUEVA SECCIÓN PROPUESTA EN):\s*\[?([a-z0-9_]+)\]?\s+UBICAR DESPUÉS DE:\s*(<<SECTION:\s*.*?>>)\s*(<<SECTION:\s*.*?>>.*?<<END SECTION>>)/su',
            $input,
            $addMatches,
            PREG_SET_ORDER
        );

        if (empty($replaceMatches) && empty($addMatches)) {
            return implode("\n", [
                '[ERROR] No se encontraron bloques válidos.',
                '[INFO] Formatos esperados:',
                '[INFO] 1) REEMPLAZAR EN: [doc_slug] + bloque SECTION completo.',
                '[INFO] 2) AGREGAR EN: [doc_slug] + UBICAR DESPUÉS DE: <<SECTION: NOMBRE>> + bloque SECTION completo.',
            ]);
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

        foreach ($replaceMatches as $match) {
            $result = $this->applyEmbeddedDocSectionReplace($match, $documents, $projectRoot);
            $output .= $result['output'];
            $lastSlug = $result['slug'] ?: $lastSlug;

            if ($result['applied']) {
                $total++;
            }
        }

        foreach ($addMatches as $match) {
            $result = $this->applyEmbeddedDocSectionAdd($match, $documents, $projectRoot);
            $output .= $result['output'];
            $lastSlug = $result['slug'] ?: $lastSlug;

            if ($result['applied']) {
                $total++;
            }
        }

        $output .= $total > 0
            ? "[OK] Proceso finalizado. Secciones aplicadas: {$total}"
            : '[WARN] Proceso finalizado sin cambios aplicados.';

        $this->log('LAB_DOCS', 'embedded-docs', $output);

        $this->appendPipeLog(
            self::LAB_LOG_DIR.'/docs-updates.log',
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
        $baseDir = $this->resolveProjectPath(self::LAB_DOCUMENTS_DIR, false);

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

    private function getTechnicalDocumentsForLab(): array
    {
        if (! class_exists(\App\Support\Docs\TechnicalDocRepository::class)) {
            return [];
        }

        try {
            $repository = app(\App\Support\Docs\TechnicalDocRepository::class);
            $documents = [];

            foreach ($repository->all() as $document) {
                if (! $this->hasExplicitTechnicalDocSlug($document)) {
                    continue;
                }

                $documents[] = $this->formatTechnicalDocumentForLab($document);
            }
        } catch (\Throwable) {
            return [];
        }

        usort($documents, fn (array $a, array $b) => strcasecmp((string) $a['title'], (string) $b['title']));

        return $documents;
    }

    private function hasExplicitTechnicalDocSlug(\App\Support\Docs\TechnicalDoc $document): bool
    {
        if (! is_file($document->sourcePath)) {
            return false;
        }

        $content = file_get_contents($document->sourcePath);

        return is_string($content) && preg_match('/^DOC_SLUG:\s*[a-z0-9_]+\s*$/mu', $content) === 1;
    }

    private function formatTechnicalDocumentForLab(\App\Support\Docs\TechnicalDoc $document): array
    {
        $projectRoot = realpath($this->projectRoot) ?: $this->projectRoot;
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $sourcePath = $document->sourcePath;
        $relativePath = str_starts_with($sourcePath, $projectRoot.DIRECTORY_SEPARATOR)
            ? ltrim(substr($sourcePath, strlen($projectRoot)), DIRECTORY_SEPARATOR)
            : $sourcePath;

        $sections = array_map(
            fn (\App\Support\Docs\TechnicalDocSection $section) => [
                'name' => $section->name,
                'body' => $section->rawBody,
                'chars' => mb_strlen($section->rawBody),
            ],
            $document->sections
        );

        return [
            'title' => $document->title,
            'slug' => $document->slug,
            'version' => $document->version,
            'path' => str_replace(DIRECTORY_SEPARATOR, '/', $relativePath),
            'section_count' => count($sections),
            'sections' => array_values($sections),
        ];
    }

    private function buildDocumentAuditPrompt(
        string $fragments,
        string $selectedSlug,
        string $selectedSection,
        array $documents,
        string $consoleOutput = ''
    ): string {
        $fragments = trim(str_replace(["\r\n", "\r"], "\n", $fragments));
        $selectedSlug = strtolower(trim($selectedSlug));
        $selectedSection = trim($selectedSection);
        $consoleOutput = trim(str_replace(["\r\n", "\r"], "\n", $consoleOutput));
        $documentAuditContext = $this->readProjectLabContextFile('document_audit_context.txt');

        if ($documentAuditContext === '') {
            $documentAuditContext = implode("\n", [
                'IA actúa como auditor documental técnico de Project Lab.',
                'No aplica cambios.',
                'No modifica archivos.',
                'Debe devolver propuestas compatibles con REEMPLAZAR EN / AGREGAR EN solo cuando corresponda.',
            ]);
        }

        $selectedDocument = null;
        $selectedSectionData = null;

        foreach ($documents as $document) {
            if (($document['slug'] ?? '') !== $selectedSlug) {
                continue;
            }

            $selectedDocument = $document;

            foreach (($document['sections'] ?? []) as $section) {
                if (($section['name'] ?? '') === $selectedSection) {
                    $selectedSectionData = $section;
                    break;
                }
            }

            break;
        }

        $documentsList = array_map(
            fn (array $document) => '- '.$document['slug'].' | '.$document['title'],
            $documents
        );

        $sectionList = [];

        if ($selectedDocument !== null) {
            $sectionList = array_map(
                fn (array $section) => '- '.$section['name'].' | caracteres: '.$section['chars'],
                $selectedDocument['sections'] ?? []
            );
        }

        $sectionBody = (string) ($selectedSectionData['body'] ?? '');

        $maxFragmentChars = 8000;
        $maxSectionChars = 14000;
        $maxConsoleChars = 4000;

        if (mb_strlen($fragments) > $maxFragmentChars) {
            $fragments = '[RECORTE AUTOMÁTICO: se conserva el final de los fragmentos]'."\n\n"
                .mb_substr($fragments, -$maxFragmentChars);
        }

        if (mb_strlen($sectionBody) > $maxSectionChars) {
            $sectionBody = '[RECORTE AUTOMÁTICO: se conserva el final de la sección seleccionada]'."\n\n"
                .mb_substr($sectionBody, -$maxSectionChars);
        }

        if (mb_strlen($consoleOutput) > $maxConsoleChars) {
            $consoleOutput = '[RECORTE AUTOMÁTICO: se conserva el final de la consola]'."\n\n"
                .mb_substr($consoleOutput, -$maxConsoleChars);
        }

        return implode("\n", [
            'AUDITORÍA DOCUMENTAL PROJECT LAB',
            '',
            'CONTEXTO DOCUMENTAL EXCLUSIVO:',
            $documentAuditContext,
            '',
            'Documentos técnicos disponibles:',
            implode("\n", $documentsList) ?: '[SIN DOCUMENTOS]',
            '',
            'Documento seleccionado:',
            $selectedDocument !== null
                ? ($selectedDocument['slug'].' | '.$selectedDocument['title'].' | '.$selectedDocument['path'])
                : '[NINGUNO O NO RECONOCIDO]',
            '',
            'Secciones del documento seleccionado:',
            implode("\n", $sectionList) ?: '[SIN DOCUMENTO SELECCIONADO]',
            '',
            'Sección seleccionada:',
            $selectedSectionData !== null ? $selectedSectionData['name'] : '[NINGUNA O NO RECONOCIDA]',
            '',
            'Contenido completo de sección seleccionada:',
            $sectionBody !== '' ? $sectionBody : '[SIN CONTENIDO DE SECCIÓN]',
            '',
            'Fragmentos o notas pegadas por el usuario:',
            $fragments !== '' ? $fragments : '[VACÍO]',
            '',
            'Consola Project Lab incluida explícitamente:',
            $consoleOutput !== '' ? $consoleOutput : '[NO INCLUIDA]',
            '',
            'Respuesta esperada:',
            'Primero explicá brevemente el diagnóstico. Luego devolvé, si aplica, bloques listos para pegar en la herramienta Docs actual. No ejecutes ni simules aplicación automática.',
        ]);
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

        if (preg_match('#(?<!tools/project-lab/)documentos/auditoria/#', $script)) {
            return '[ERROR] La auditoría de Project Lab debe escribir en '.self::LAB_AUDIT_DIR.'/. La carpeta raíz documentos/ queda reservada para documentación funcional del producto.';
        }

        $hasExplicitAuditOutput = str_contains($script, self::LAB_AUDIT_DIR.'/');

        if ($hasExplicitAuditOutput) {
            $result = $this->executeAuditScript($script);

            $this->log('AUDIT_SCRIPT', 'audit-inline', $result);

            return $result;
        }

        $timestamp = date('Ymd_His');
        $relativeOutput = self::LAB_AUDIT_DIR."/auditoria_{$timestamp}.txt";

        $wrapped = "{\n";
        $wrapped .= "echo \"[OK] Auditoría simple ejecutada.\";\n";
        $wrapped .= "echo \"[OK] Archivo generado: {$relativeOutput}\";\n";
        $wrapped .= "echo \"[OK] Proyecto: {$this->projectRoot}\";\n";
        $wrapped .= "echo \"[INFO] Entrada: {$inputPreview}\";\n";
        $wrapped .= "echo \"\";\n";
        $wrapped .= $script."\n";
        $wrapped .= '} 2>&1 | tee '.escapeshellarg($relativeOutput);

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
            $style = str_starts_with($headerLine, '#') ? 'hash' : 'line';
        } elseif (preg_match(self::REGEX_PLAIN_FILE_HEADER_BLOCK, $headerLine, $matches)) {
            $style = 'block';
        } else {
            return '[ERROR] No se encontró encabezado FILE válido para CSS/JS/TPL.';
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

        if ($mode === 'tpl_full' && $extension !== 'tpl') {
            return "[ERROR] El modo TPL requiere archivo .tpl: {$relativePath}";
        }

        $targetPath = $this->resolveProjectPath($relativePath, true);

        if ($targetPath === null) {
            return "[ERROR] Ruta fuera del proyecto o no permitida: {$relativePath}";
        }

        array_shift($lines);
        $body = ltrim(implode("\n", $lines), "\n");

        if ($style === 'hash') {
            $header = "# FILE: {$relativePath} | {$version}";
        } elseif ($style === 'line') {
            $header = "// FILE: {$relativePath} | {$version}";
        } else {
            $header = "/* FILE: {$relativePath} | {$version} */";
        }

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
        $relativeOutput = self::LAB_AUDIT_DIR."/consola_project_lab_{$timestamp}.txt";

        $header = "[OK] Consola Project Lab guardada como auditoría\n";
        $header .= "[OK] Archivo generado: {$relativeOutput}\n";
        $header .= "[OK] Proyecto: {$this->projectRoot}\n";
        $header .= '[OK] Fecha: '.date('Y-m-d H:i:s')."\n\n";

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
        $relativePath = str_replace(["\r\n", "\r", "\n", '\\'], ['', '', '', '/'], trim($relativePath));

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
                        .'[ERROR] Resultado rollback: '.$rollbackResult,
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
        return $this->ensureProjectDirectory(self::LAB_AUDIT_DIR);
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

        if (! preg_match('/^>>\s*([a-z0-9_-]+)(?:\s+(.*))?$/i', $input, $commandMatch)) {
            return [
                'matched' => true,
                'ok' => false,
                'input' => $input,
                'error' => implode("\n", [
                    '[ERROR] Comando rápido Project Lab inválido.',
                    '[INFO] Formato esperado:',
                    '>> <macro> [argumentos]',
                    '',
                    '[INFO] Ejemplos:',
                    '>> find web.php service +30',
                    '>> find ProjectLab.php "function resolveQuickAuditCommand" +80',
                    '>> test-macro A B',
                ]),
            ];
        }

        $macroName = strtolower(trim((string) ($commandMatch[1] ?? '')));
        $rawArgs = trim((string) ($commandMatch[2] ?? ''));

        if ($macroName === '') {
            return [
                'matched' => true,
                'ok' => false,
                'input' => $input,
                'error' => '[ERROR] No se indicó nombre de macro.',
            ];
        }

        if (! preg_match('/^[a-z0-9_-]+$/i', $macroName)) {
            return [
                'matched' => true,
                'ok' => false,
                'input' => $input,
                'error' => '[ERROR] Nombre de macro inválido. Use solo letras, números, guion medio o guion bajo.',
            ];
        }

        if ($macroName === 'find') {
            $args = $this->parseQuickAuditArguments($rawArgs);
            $count = count($args);

            if ($count < 2 || $count > 3) {
                return [
                    'matched' => true,
                    'ok' => false,
                    'input' => $input,
                    'error' => implode("\n", [
                        '[ERROR] Comando rápido Project Lab inválido.',
                        '[INFO] Formato esperado:',
                        '>> find <archivo|*> <termino> +<lineas>',
                        '',
                        '[INFO] También se acepta término entre comillas:',
                        '>> find <archivo|*> "<termino con espacios>" +<lineas>',
                        '',
                        '[INFO] Ejemplos:',
                        '>> find * executeTinker +50',
                        '>> find ProjectLab.php executeTinker +50',
                        '>> find ProjectLab.php "function executeTinker" +80',
                        '>> find app.js "function runProjectAction" +120',
                    ]),
                ];
            }

            $filePattern = trim((string) ($args[0] ?? ''));
            $term = trim((string) ($args[1] ?? ''));
            $lines = 30;

            if (isset($args[2])) {
                $lineArg = trim((string) $args[2]);

                if (! preg_match('/^\+(\d+)$/', $lineArg, $lineMatch)) {
                    return [
                        'matched' => true,
                        'ok' => false,
                        'input' => $input,
                        'error' => implode("\n", [
                            '[ERROR] Cantidad de líneas inválida.',
                            '[INFO] Use el formato +<lineas>.',
                            '[INFO] Ejemplo:',
                            '>> find ProjectLab.php "function executeTinker" +80',
                        ]),
                    ];
                }

                $lines = (int) ($lineMatch[1] ?? 30);
            }

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

        $template = $this->loadAuditMacroTemplate($macroName);

        if ($template === '') {
            $availableMacros = $this->listAuditMacros();

            return [
                'matched' => true,
                'ok' => false,
                'input' => $input,
                'error' => implode("\n", [
                    "[ERROR] Macro Project Lab no encontrada: {$macroName}",
                    '[INFO] Ruta esperada:',
                    "tools/project-lab/macros/audit/{$macroName}.sh.tpl",
                    '',
                    '[INFO] Macros disponibles:',
                    empty($availableMacros) ? '- ninguna' : '- '.implode("\n- ", $availableMacros),
                ]),
            ];
        }

        $args = $this->parseQuickAuditArguments($rawArgs);
        $rules = $this->readAuditMacroArgumentRules($template);
        $count = count($args);

        if ($count < $rules['min']) {
            return [
                'matched' => true,
                'ok' => false,
                'input' => $input,
                'error' => "[ERROR] La macro {$macroName} requiere al menos {$rules['min']} argumento/s. Recibidos: {$count}.",
            ];
        }

        if ($count > $rules['max']) {
            return [
                'matched' => true,
                'ok' => false,
                'input' => $input,
                'error' => "[ERROR] La macro {$macroName} acepta como máximo {$rules['max']} argumento/s. Recibidos: {$count}.",
            ];
        }

        return [
            'matched' => true,
            'ok' => true,
            'input' => $this->buildGenericAuditMacroScript($macroName, $template, $input, $rawArgs, $args),
            'original_input' => $input,
            'command' => $macroName,
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

    private function listAuditMacros(): array
    {
        $relativePath = 'tools/project-lab/macros/audit';
        $directory = $this->resolveProjectPath($relativePath, false);

        if ($directory === null || ! is_dir($directory)) {
            return [];
        }

        $macros = [];

        foreach (glob($directory.'/*.sh.tpl') ?: [] as $filePath) {
            $name = basename($filePath, '.sh.tpl');

            if (preg_match('/^[a-z0-9_-]+$/i', $name)) {
                $macros[] = $name;
            }
        }

        sort($macros);

        return $macros;
    }

    private function parseQuickAuditArguments(string $rawArgs): array
    {
        $rawArgs = trim($rawArgs);

        if ($rawArgs === '') {
            return [];
        }

        $args = str_getcsv($rawArgs, ' ', '"', '\\');

        return array_values(array_filter(array_map('trim', $args), static function (string $arg): bool {
            return $arg !== '';
        }));
    }

    private function readAuditMacroArgumentRules(string $template): array
    {
        $min = 0;
        $max = 10;

        if (preg_match('/^\s*#\s*PROJECT_LAB_MACRO_ARGS_MIN:\s*(\d+)\s*$/mi', $template, $matches)) {
            $min = max(0, (int) ($matches[1] ?? 0));
        }

        if (preg_match('/^\s*#\s*PROJECT_LAB_MACRO_ARGS_MAX:\s*(\d+)\s*$/mi', $template, $matches)) {
            $max = max($min, (int) ($matches[1] ?? $max));
        }

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    private function buildGenericAuditMacroScript(
        string $macroName,
        string $template,
        string $originalInput,
        string $rawArgs,
        array $args
    ): string {
        $values = [
            'MACRO_NAME' => $this->escapeDoubleQuotedShellEcho($macroName),
            'MACRO_NAME_SHELL' => escapeshellarg($macroName),
            'ORIGINAL_INPUT' => $this->escapeDoubleQuotedShellEcho($originalInput),
            'ORIGINAL_INPUT_SHELL' => escapeshellarg($originalInput),
            'RAW_ARGS' => $this->escapeDoubleQuotedShellEcho($rawArgs),
            'RAW_ARGS_SHELL' => escapeshellarg($rawArgs),
            'ARG_COUNT' => (string) count($args),
        ];

        for ($i = 1; $i <= 10; $i++) {
            $value = $args[$i - 1] ?? '';

            $values['ARG_'.$i] = $this->escapeDoubleQuotedShellEcho($value);
            $values['ARG_'.$i.'_SHELL'] = escapeshellarg($value);
        }

        return $this->renderAuditMacroTemplate($template, $values);
    }

    private function askLocalAi(string $model, string $prompt): string
    {
        $allowedModels = [
            'qwen2.5:1.5b',
            'qwen2.5:3b',
        ];

        $model = trim($model);
        $prompt = trim(str_replace(["\r\n", "\r"], "\n", $prompt));

        if (! in_array($model, $allowedModels, true)) {
            return implode("\n", [
                '[ERROR] Modelo IA local no permitido.',
                '[INFO] Modelos permitidos:',
                '- qwen2.5:1.5b',
                '- qwen2.5:3b',
            ]);
        }

        if ($prompt === '') {
            return '[ERROR] No se recibió prompt para IA local.';
        }

        $endpoint = 'http://127.0.0.1:11434/api/generate';
        $finalPrompt = $this->buildLocalAiPrompt($prompt);

        $payload = json_encode([
            'model' => $model,
            'prompt' => $finalPrompt,
            'stream' => false,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($payload)) {
            return '[ERROR] No se pudo preparar el payload JSON para Ollama.';
        }

        $timeoutSeconds = 45;

        $response = null;
        $httpCode = null;
        $transportError = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($endpoint);

            if ($ch === false) {
                return '[ERROR] No se pudo inicializar cURL para consultar Ollama.';
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => $timeoutSeconds,
            ]);

            $curlResponse = curl_exec($ch);

            if ($curlResponse === false) {
                $transportError = curl_error($ch);
            } else {
                $response = $curlResponse;
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }

            curl_close($ch);
        } else {
            if (! filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
                return implode("\n", [
                    '[ERROR] No hay transporte HTTP disponible para consultar Ollama.',
                    '[INFO] cURL no está disponible y allow_url_fopen está deshabilitado.',
                ]);
            }

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", [
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ]),
                    'content' => $payload,
                    'timeout' => $timeoutSeconds,
                    'ignore_errors' => true,
                ],
            ]);

            $streamResponse = @file_get_contents($endpoint, false, $context);

            if ($streamResponse === false) {
                $transportError = 'file_get_contents no pudo conectar con Ollama.';
            } else {
                $response = $streamResponse;

                $headers = [];

                if (function_exists('http_get_last_response_headers')) {
                    $lastHeaders = http_get_last_response_headers();
                    $headers = is_array($lastHeaders) ? $lastHeaders : [];
                }

                foreach ($headers as $headerLine) {
                    if (preg_match('/^HTTP\/\S+\s+(\d+)/', $headerLine, $matches)) {
                        $httpCode = (int) ($matches[1] ?? 0);
                        break;
                    }
                }
            }
        }

        if ($response === null || $response === '') {
            return implode("\n", array_filter([
                '[ERROR] Ollama local no respondió.',
                '[INFO] Endpoint esperado: '.$endpoint,
                '[INFO] Verificar servicio: systemctl status ollama.service',
                $transportError ? '[INFO] Error transporte: '.$transportError : null,
            ]));
        }

        if ($httpCode !== null && ($httpCode < 200 || $httpCode >= 300)) {
            return implode("\n", [
                '[ERROR] Ollama local respondió con error HTTP.',
                '[INFO] HTTP status: '.$httpCode,
                '[INFO] Endpoint: '.$endpoint,
                '',
                '--- RESPUESTA RAW ---',
                $response,
            ]);
        }

        $data = json_decode($response, true);

        if (! is_array($data)) {
            return implode("\n", [
                '[ERROR] Ollama devolvió una respuesta no JSON.',
                '',
                '--- RESPUESTA RAW ---',
                $response,
            ]);
        }

        $answer = trim((string) ($data['response'] ?? ''));

        if ($answer === '') {
            return implode("\n", [
                '[WARN] Ollama respondió sin campo response visible.',
                '',
                '--- RESPUESTA RAW ---',
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $response,
            ]);
        }

        return implode("\n", [
            '[OK] Consulta IA local completada.',
            '[INFO] Modelo: '.$model,
            '[INFO] Endpoint: Ollama local',
            '[INFO] Stream: false',
            '',
            '--- RESPUESTA IA LOCAL ---',
            $answer,
        ]);
    }

    private function buildLocalAiPrompt(string $prompt): string
    {
        $systemPrompt = implode("\n", [
            'Respondé en español.',
            'Modo Project Lab.',
            'Sé breve, claro y operativo.',
            'NO SE ASUME. NO SE SUPONE.',
            'No inventes evidencia.',
            'No afirmes ejecutar, leer o modificar.',
            'No propongas acciones automáticas.',
            'Si falta evidencia, pedí el dato exacto mínimo.',
            'Si hay evidencia suficiente, indicá próximo paso compatible con Project Lab.',
        ]);

        return $systemPrompt."\n\n--- INPUT ---\n".$prompt;
    }

    private function askAiPrompt(string $modelKey, string $prompt): string
    {
        $modelKey = trim($modelKey);

        if ($modelKey === '') {
            return '[ERROR] No se recibió modelo IA.';
        }

        if (str_starts_with($modelKey, 'ollama:')) {
            $model = substr($modelKey, strlen('ollama:'));

            return $this->askLocalAi($model, $prompt);
        }

        if (str_starts_with($modelKey, 'gemini:')) {
            $model = substr($modelKey, strlen('gemini:'));

            return $this->askGeminiAi($model, $prompt);
        }

        if (str_starts_with($modelKey, 'codex:')) {
            $model = substr($modelKey, strlen('codex:'));

            return $this->askCodexAi($model, $prompt);
        }

        return implode("\n", [
            '[ERROR] Proveedor IA no reconocido.',
            '[INFO] Modelos disponibles:',
            '- ollama:qwen2.5:1.5b',
            '- ollama:qwen2.5:3b',
            '- gemini:gemini-2.5-flash',
            '- codex:default',
        ]);
    }

    private function readProjectLabSecret(string $name): string
    {
        if (! preg_match('/^[a-z0-9_\-]+$/i', $name)) {
            return '';
        }

        $path = $this->resolveProjectPath("tools/project-lab/.secrets/{$name}", false);

        if ($path === null || ! is_file($path)) {
            return '';
        }

        $value = file_get_contents($path);

        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }

    private function askGeminiAi(string $model, string $prompt): string
    {
        $allowedModels = [
            'gemini-2.5-flash',
        ];

        $model = trim($model);
        $prompt = trim(str_replace(["\r\n", "\r"], "\n", $prompt));

        if (! in_array($model, $allowedModels, true)) {
            return implode("\n", [
                '[ERROR] Modelo Gemini no permitido.',
                '[INFO] Modelos permitidos:',
                '- gemini-2.5-flash',
            ]);
        }

        if ($prompt === '') {
            return '[ERROR] No se recibió prompt para Gemini.';
        }

        $apiKey = $this->readProjectLabSecret('gemini_api_key');

        if ($apiKey === '') {
            return implode("\n", [
                '[ERROR] No se encontró API key de Gemini.',
                '[INFO] Crear archivo local:',
                'tools/project-lab/.secrets/gemini_api_key',
                '[INFO] El archivo debe contener solo la API key.',
            ]);
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        $finalPrompt = $this->buildLocalAiPrompt($prompt);

        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $finalPrompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($payload)) {
            return '[ERROR] No se pudo preparar el payload JSON para Gemini.';
        }

        $timeoutSeconds = 45;

        $response = null;
        $httpCode = null;
        $transportError = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($endpoint);

            if ($ch === false) {
                return '[ERROR] No se pudo inicializar cURL para consultar Gemini.';
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'x-goog-api-key: '.$apiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => $timeoutSeconds,
            ]);

            $curlResponse = curl_exec($ch);

            if ($curlResponse === false) {
                $transportError = curl_error($ch);
            } else {
                $response = $curlResponse;
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }

            curl_close($ch);
        } else {
            if (! filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
                return implode("\n", [
                    '[ERROR] No hay transporte HTTP disponible para consultar Gemini.',
                    '[INFO] cURL no está disponible y allow_url_fopen está deshabilitado.',
                ]);
            }

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", [
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'x-goog-api-key: '.$apiKey,
                    ]),
                    'content' => $payload,
                    'timeout' => $timeoutSeconds,
                    'ignore_errors' => true,
                ],
            ]);

            $streamResponse = @file_get_contents($endpoint, false, $context);

            if ($streamResponse === false) {
                $transportError = 'file_get_contents no pudo conectar con Gemini.';
            } else {
                $response = $streamResponse;

                $headers = [];

                if (function_exists('http_get_last_response_headers')) {
                    $lastHeaders = http_get_last_response_headers();
                    $headers = is_array($lastHeaders) ? $lastHeaders : [];
                }

                foreach ($headers as $headerLine) {
                    if (preg_match('/^HTTP\/\S+\s+(\d+)/', $headerLine, $matches)) {
                        $httpCode = (int) ($matches[1] ?? 0);
                        break;
                    }
                }
            }
        }

        if ($response === null || $response === '') {
            return implode("\n", array_filter([
                '[ERROR] Gemini no respondió.',
                '[INFO] Endpoint: Gemini API generateContent',
                $transportError ? '[INFO] Error transporte: '.$transportError : null,
            ]));
        }

        if ($httpCode !== null && ($httpCode < 200 || $httpCode >= 300)) {
            return implode("\n", [
                '[ERROR] Gemini respondió con error HTTP.',
                '[INFO] HTTP status: '.$httpCode,
                '[INFO] Modelo: '.$model,
                '[INFO] No se muestra la API key.',
                '',
                '--- RESPUESTA RAW ---',
                $response,
            ]);
        }

        $data = json_decode($response, true);

        if (! is_array($data)) {
            return implode("\n", [
                '[ERROR] Gemini devolvió una respuesta no JSON.',
                '',
                '--- RESPUESTA RAW ---',
                $response,
            ]);
        }

        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        $answerParts = [];

        if (is_array($parts)) {
            foreach ($parts as $part) {
                if (is_array($part) && isset($part['text'])) {
                    $answerParts[] = trim((string) $part['text']);
                }
            }
        }

        $answer = trim(implode("\n", array_filter($answerParts)));

        if ($answer === '') {
            return implode("\n", [
                '[WARN] Gemini respondió sin texto visible.',
                '[INFO] No se muestra la API key.',
                '',
                '--- RESPUESTA RAW ---',
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $response,
            ]);
        }

        return implode("\n", [
            '[OK] Consulta IA completada.',
            '[INFO] Proveedor: Gemini',
            '[INFO] Modelo: '.$model,
            '[INFO] Endpoint: generateContent',
            '[INFO] API key: configurada localmente',
            '',
            '--- RESPUESTA IA ---',
            $answer,
        ]);
    }

    private function readProjectLabContextFile(string $name): string
    {
        if (! preg_match('/^[a-z0-9_\-]+\.txt$/i', $name)) {
            return '';
        }

        $path = $this->resolveProjectPath("tools/project-lab/context/{$name}", false);

        if ($path === null || ! is_file($path)) {
            return '';
        }

        $content = file_get_contents($path);

        if (! is_string($content)) {
            return '';
        }

        return trim(str_replace(["\r\n", "\r"], "\n", $content));
    }

    private function buildProjectLabAiUserPrompt(string $editablePrompt, string $consoleOutput): string
    {
        $editablePrompt = trim(str_replace(["\r\n", "\r"], "\n", $editablePrompt));
        $consoleOutput = trim(str_replace(["\r\n", "\r"], "\n", $consoleOutput));

        $maxEditablePromptChars = 6000;
        $maxConsoleChars = 12000;

        if (mb_strlen($editablePrompt) > $maxEditablePromptChars) {
            $editablePrompt = '[RECORTE AUTOMÁTICO: se conserva el final del prompt editable]'."\n\n"
                .mb_substr($editablePrompt, -$maxEditablePromptChars);
        }

        if (mb_strlen($consoleOutput) > $maxConsoleChars) {
            $consoleOutput = '[RECORTE AUTOMÁTICO: se conserva el final de la consola Project Lab]'."\n\n"
                .mb_substr($consoleOutput, -$maxConsoleChars);
        }

        $taskContext = $this->readProjectLabContextFile('ai_task_context.txt');

        if ($taskContext === '') {
            $taskContext = implode("\n", [
                'TAREA:',
                '',
                'Respondé usando el prompt editable del usuario y la evidencia de consola.',
                'Si el prompt editable está vacío, analizá la consola.',
                'Identificá el error principal.',
                'No inventes.',
                'No supongas.',
                'Si falta evidencia, pedí el dato exacto.',
                'Si hay evidencia suficiente, proponé el próximo paso compatible con Project Lab.',
            ]);
        }

        return implode("\n", [
            'PROMPT EDITABLE DEL USUARIO:',
            '',
            $editablePrompt !== '' ? $editablePrompt : '[VACÍO]',
            '',
            'EVIDENCIA ACTUAL DE CONSOLA PROJECT LAB:',
            '',
            $consoleOutput !== '' ? $consoleOutput : '[SIN SALIDA DE CONSOLA]',
            '',
            $taskContext,
        ]);
    }

    private function buildAiConsoleUserBlock(string $model, bool $fromClipboard, string $editablePrompt, string $consoleOutput): string
    {
        $editablePrompt = trim(str_replace(["\r\n", "\r"], "\n", $editablePrompt));
        $consoleOutput = trim(str_replace(["\r\n", "\r"], "\n", $consoleOutput));

        $maxPreviewChars = 1200;

        $promptPreview = $editablePrompt;

        if (mb_strlen($promptPreview) > $maxPreviewChars) {
            $promptPreview = '[RECORTE VISUAL: se muestra el final del prompt]'."\n\n"
                .mb_substr($promptPreview, -$maxPreviewChars);
        }

        if ($promptPreview === '') {
            $promptPreview = '[VACÍO]';
        }

        return implode("\n", [
            '[IA_USER]',
            'Prompt enviado a IA',
            '',
            'Modelo: '.$model,
            'Origen: '.($fromClipboard ? 'clipboard + consola' : 'textarea + consola'),
            'Consola incluida: '.($consoleOutput !== '' ? 'sí' : 'no'),
            'Caracteres consola: '.mb_strlen($consoleOutput),
            '',
            'Prompt editable / entrada:',
            $promptPreview,
            '[/IA_USER]',
        ]);
    }

    private function buildAiConsoleAssistantBlock(string $output): string
    {
        $output = trim(str_replace(["\r\n", "\r"], "\n", $output));

        if ($output === '') {
            $output = '[WARN] IA respondió sin salida visible.';
        }

        return implode("\n", [
            '[IA_ASSISTANT]',
            $output,
            '[/IA_ASSISTANT]',
        ]);
    }

    private function applyEmbeddedDocSectionReplace(array $match, array $documents, string $projectRoot): array
    {
        $slug = strtolower(trim($match[1] ?? ''));
        $block = trim($match[2] ?? '');

        if (! isset($documents[$slug])) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] Documento no reconocido por slug: {$slug}\n",
            ];
        }

        if (! preg_match('/<<SECTION:\s*(.*?)\s*>>/su', $block, $sectionMatch)) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] No se pudo leer el nombre de sección para {$slug}\n",
            ];
        }

        if (! preg_match('/\nSECTION_VERSION:\s*\d{5}\s*(?:\n|$)/u', "\n".$block)) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] El bloque no incluye SECTION_VERSION válido de 5 dígitos.\n",
            ];
        }

        $sectionName = trim($sectionMatch[1]);
        $filePath = $documents[$slug];
        $resolvedFilePath = realpath($filePath);

        if ($resolvedFilePath === false) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] No se pudo resolver documento: {$slug}\n",
            ];
        }

        if (! str_starts_with($resolvedFilePath, $projectRoot.DIRECTORY_SEPARATOR)) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] Documento fuera del proyecto: {$slug}\n",
            ];
        }

        $relativeDocPath = ltrim(substr($resolvedFilePath, strlen($projectRoot)), DIRECTORY_SEPARATOR);
        $content = file_get_contents($resolvedFilePath);

        if ($content === false) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] No se pudo leer documento: {$slug}\n",
            ];
        }

        $pattern = '/<<SECTION:\s*'.preg_quote($sectionName, '/').'\s*>>.*?<<END SECTION>>/su';

        if (! preg_match($pattern, $content)) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] No se encontró la sección: {$sectionName}\n",
            ];
        }

        $timestamp = date('Ymd_His');
        $backupRelativePath = self::LAB_BACKUP_DIR.'/'.pathinfo($resolvedFilePath, PATHINFO_FILENAME)."_{$timestamp}.bak";
        $backupResult = $this->writeProjectFile($backupRelativePath, $content, true);

        if ($backupResult !== true) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] No se pudo guardar backup: {$backupResult}\n",
            ];
        }

        $newContent = preg_replace($pattern, $block, $content, 1, $count);

        if ($count < 1 || $newContent === null) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] No se pudo reemplazar la sección: {$sectionName}\n",
            ];
        }

        $writeResult = $this->writeProjectFile($relativeDocPath, $newContent, false);

        if ($writeResult !== true) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] No se pudo escribir el documento. {$writeResult}\n",
            ];
        }

        return [
            'slug' => $slug,
            'applied' => true,
            'output' => "[OK] [{$slug}] Sección reemplazada: {$sectionName}\n[OK] [{$slug}] Backup: {$backupRelativePath}\n",
        ];
    }

    private function applyEmbeddedDocSectionAdd(array $match, array $documents, string $projectRoot): array
    {
        $slug = strtolower(trim($match[1] ?? ''));
        $anchorHeader = trim($match[2] ?? '');
        $block = trim($match[3] ?? '');

        if (! isset($documents[$slug])) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] Documento no reconocido por slug: {$slug}\n",
            ];
        }

        if (! preg_match('/<<SECTION:\s*(.*?)\s*>>/su', $anchorHeader, $anchorMatch)) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] No se pudo leer la sección ancla.\n",
            ];
        }

        if (! preg_match('/<<SECTION:\s*(.*?)\s*>>/su', $block, $sectionMatch)) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] No se pudo leer el nombre de sección nueva para {$slug}\n",
            ];
        }

        if (! preg_match('/\nSECTION_VERSION:\s*\d{5}\s*(?:\n|$)/u', "\n".$block)) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] El bloque nuevo no incluye SECTION_VERSION válido de 5 dígitos.\n",
            ];
        }

        $anchorName = trim($anchorMatch[1]);
        $sectionName = trim($sectionMatch[1]);

        if ($anchorName === '' || $sectionName === '') {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] Sección ancla o sección nueva vacía.\n",
            ];
        }

        if ($anchorName === $sectionName) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] La sección nueva no puede tener el mismo nombre que el ancla: {$sectionName}\n",
            ];
        }

        $filePath = $documents[$slug];
        $resolvedFilePath = realpath($filePath);

        if ($resolvedFilePath === false) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] No se pudo resolver documento: {$slug}\n",
            ];
        }

        if (! str_starts_with($resolvedFilePath, $projectRoot.DIRECTORY_SEPARATOR)) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] Documento fuera del proyecto: {$slug}\n",
            ];
        }

        $relativeDocPath = ltrim(substr($resolvedFilePath, strlen($projectRoot)), DIRECTORY_SEPARATOR);
        $content = file_get_contents($resolvedFilePath);

        if ($content === false) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] No se pudo leer documento: {$slug}\n",
            ];
        }

        $newSectionPattern = '/<<SECTION:\s*'.preg_quote($sectionName, '/').'\s*>>.*?<<END SECTION>>/su';

        if (preg_match($newSectionPattern, $content)) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] La sección ya existe: {$sectionName}. Use REEMPLAZAR EN.\n",
            ];
        }

        $anchorPattern = '/<<SECTION:\s*'.preg_quote($anchorName, '/').'\s*>>.*?<<END SECTION>>/su';

        $anchorCount = preg_match_all($anchorPattern, $content, $anchorMatches, PREG_OFFSET_CAPTURE);

        if ($anchorCount < 1) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] No se encontró la sección ancla: {$anchorName}\n",
            ];
        }

        if ($anchorCount > 1) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] La sección ancla aparece más de una vez: {$anchorName}\n",
            ];
        }

        $anchorBlock = $anchorMatches[0][0][0] ?? '';
        $anchorOffset = $anchorMatches[0][0][1] ?? null;

        if ($anchorBlock === '' || $anchorOffset === null) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] No se pudo resolver la posición del ancla: {$anchorName}\n",
            ];
        }

        $insertPosition = $anchorOffset + strlen($anchorBlock);
        $newContent = substr($content, 0, $insertPosition)
            ."\n\n".$block
            .substr($content, $insertPosition);

        $timestamp = date('Ymd_His');
        $backupRelativePath = self::LAB_BACKUP_DIR.'/'.pathinfo($resolvedFilePath, PATHINFO_FILENAME)."_{$timestamp}.bak";
        $backupResult = $this->writeProjectFile($backupRelativePath, $content, true);

        if ($backupResult !== true) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] No se pudo guardar backup: {$backupResult}\n",
            ];
        }

        $writeResult = $this->writeProjectFile($relativeDocPath, $newContent, false);

        if ($writeResult !== true) {
            return [
                'slug' => $slug,
                'applied' => false,
                'output' => "[ERROR] [{$slug}] No se pudo escribir el documento. {$writeResult}\n",
            ];
        }

        return [
            'slug' => $slug,
            'applied' => true,
            'output' => "[OK] [{$slug}] Sección agregada: {$sectionName}\n[OK] [{$slug}] Ubicada después de: {$anchorName}\n[OK] [{$slug}] Backup: {$backupRelativePath}\n",
        ];
    }

    private function askCodexAi(string $model, string $prompt): string
    {
        $model = trim($model);

        if ($model === '') {
            $model = 'default';
        }

        $allowedModels = [
            'default',
        ];

        if (! in_array($model, $allowedModels, true)) {
            return implode("\n", [
                '[ERROR] Modelo Codex no permitido.',
                '[INFO] Modelos permitidos:',
                '- codex:default',
            ]);
        }

        $prompt = trim($prompt);

        if ($prompt === '') {
            return '[ERROR] No se recibió prompt para Codex.';
        }

        $home = getenv('HOME') ?: '/home/alejandro';

        $pathParts = [
            $home.'/.local/npm-global/bin',
            '/home/alejandro/.local/npm-global/bin',
            '/usr/local/bin',
            '/usr/bin',
            '/bin',
        ];

        $path = implode(':', array_values(array_unique(array_filter($pathParts))));

        $codexBin = trim((string) shell_exec('PATH='.escapeshellarg($path).' command -v codex 2>/dev/null'));

        if ($codexBin === '' && is_executable('/home/alejandro/.local/npm-global/bin/codex')) {
            $codexBin = '/home/alejandro/.local/npm-global/bin/codex';
        }

        if ($codexBin === '') {
            return implode("\n", [
                '[ERROR] Codex CLI no disponible para el proceso PHP.',
                '[INFO] Verificar instalación:',
                'which codex',
                'codex --version',
                '[INFO] Si desde terminal funciona pero desde Project Lab no, revisar PATH/HOME del proceso web.',
            ]);
        }

        $codexPrompt = implode("\n\n", [
            'Proyecto app-base Laravel multi-tenant.',
            'Regla máxima: NO SE ASUME. NO SE SUPONE.',
            'Modo Project Lab / Codex CLI.',
            'No modifiques archivos.',
            'No crees archivos.',
            'No ejecutes comandos destructivos.',
            'No cambies código.',
            'No cambies documentación.',
            'Trabajá sobre evidencia real del workspace.',
            'Respondé en español.',
            'TAREA:',
            $prompt,
        ]);

        $command = 'PATH='.escapeshellarg($path)
            .' HOME='.escapeshellarg($home)
            .' '.escapeshellcmd($codexBin)
            .' exec'
            .' -s read-only'
            .' --cd '.escapeshellarg($this->projectRoot)
            .' '.escapeshellarg($codexPrompt);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $this->projectRoot);

        if (! is_resource($process)) {
            return '[ERROR] No se pudo iniciar Codex CLI.';
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $startedAt = time();
        $timeoutSeconds = 240;

        while (true) {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            $status = proc_get_status($process);

            if (! ($status['running'] ?? false)) {
                break;
            }

            if ((time() - $startedAt) > $timeoutSeconds) {
                proc_terminate($process);

                return implode("\n", [
                    '[ERROR] Codex CLI excedió el tiempo máximo de ejecución.',
                    '[INFO] Timeout: '.$timeoutSeconds.' segundos.',
                    '[INFO] Sugerencia: usar un prompt más acotado o ejecutar Codex directo desde terminal.',
                    '',
                    '--- STDOUT PARCIAL ---',
                    trim($stdout) !== '' ? trim($stdout) : '(sin salida)',
                    '',
                    '--- STDERR PARCIAL ---',
                    trim($stderr) !== '' ? trim($stderr) : '(sin salida)',
                ]);
            }

            usleep(100000);
        }

        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        $result = [
            $exitCode === 0 ? '[OK] Consulta Codex completada.' : '[ERROR] Codex respondió con error.',
            '[INFO] Proveedor: codex',
            '[INFO] Modelo: default',
            '[INFO] Sandbox: read-only',
            '[INFO] ProjectRoot: '.$this->projectRoot,
        ];

        if (trim($stderr) !== '') {
            $result[] = '';
            $result[] = '--- STDERR CODEX ---';
            $result[] = trim($stderr);
        }

        $result[] = '';
        $result[] = '--- RESPUESTA CODEX ---';
        $result[] = trim($stdout) !== '' ? trim($stdout) : '(sin salida visible)';

        return implode("\n", $result);
    }
}
