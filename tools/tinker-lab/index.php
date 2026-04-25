<?php

// FILE: tools/tinker-lab/index.php | V4

$projectRoot = dirname(__DIR__, 2);
$logDir = $projectRoot.'/documentos/log';
$logFile = $logDir.'/tinker-lab.log';

if (! is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

$case = $_POST['case'] ?? '';
$code = $_POST['code'] ?? '';
$output = $_POST['output'] ?? '';
$log = $_POST['log'] ?? '';

$artisanCommands = [
    'migrate' => 'php artisan migrate',
    'migrate_fresh_seed' => 'php artisan migrate:fresh --seed',
    'optimize_clear' => 'php artisan optimize:clear',
    'routes_metrics' => 'php artisan route:list',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run'])) {
    $command = 'cd '.escapeshellarg($projectRoot)
        .' && php artisan tinker --execute='.escapeshellarg($code)
        .' 2>&1';

    $output = shell_exec($command) ?? '';

    $now = date('Y-m-d H:i:s');
    $safeCase = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $case ?: 'sin-caso');

    $log = <<<TXT
============================================================
TINKER LAB LOG
============================================================
FECHA: {$now}
CASO: {$safeCase}
COMANDO: php artisan tinker --execute
============================================================

SALIDA:
{$output}

============================================================
FIN TINKER LAB LOG
============================================================

TXT;

    file_put_contents(
        $logFile,
        $log.PHP_EOL.PHP_EOL,
        FILE_APPEND
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['artisan'])) {
    $artisanKey = (string) $_POST['artisan'];

    if (array_key_exists($artisanKey, $artisanCommands)) {
        $artisanCommand = $artisanCommands[$artisanKey];
        $command = 'cd '.escapeshellarg($projectRoot).' && '.$artisanCommand.' 2>&1';

        $output = shell_exec($command) ?? '';

        $now = date('Y-m-d H:i:s');
        $safeCase = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $case ?: 'artisan-'.$artisanKey);

        $log = <<<TXT
============================================================
TINKER LAB ARTISAN LOG
============================================================
FECHA: {$now}
CASO: {$safeCase}
COMANDO: {$artisanCommand}
============================================================

SALIDA:
{$output}

============================================================
FIN TINKER LAB ARTISAN LOG
============================================================

TXT;

        file_put_contents(
            $logFile,
            $log.PHP_EOL.PHP_EOL,
            FILE_APPEND
        );
    } else {
        $output = 'Comando Artisan no permitido.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_output'])) {
    $output = '';
    $log = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all'])) {
    $case = '';
    $code = '';
    $output = '';
    $log = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_session'])) {
    $now = date('Y-m-d H:i:s');

    $sessionBlock = <<<TXT
============================================================
CIERRE DE SESIÓN TINKER LAB
============================================================
FECHA: {$now}
============================================================


============================================================
NUEVA SESIÓN TINKER LAB
============================================================
FECHA: {$now}
============================================================

TXT;

    file_put_contents(
        $logFile,
        $sessionBlock.PHP_EOL,
        FILE_APPEND
    );

    $output = '';
    $log = '';
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tinker Lab</title>
    <style>
        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #e5e7eb;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0;
        }

        .tools-box {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            background: #020617;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 10px;
        }

        .tools-box-label {
            color: #9ca3af;
            font-size: 12px;
            margin-right: 4px;
        }

        .help {
            background: #020617;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .help code {
            background: #111827;
            padding: 2px 6px;
            border-radius: 6px;
            color: #93c5fd;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .card {
            background: #111827;
            border: 1px solid #374151;
            border-radius: 12px;
            padding: 16px;
        }

        .full { grid-column: 1 / -1; }

        input,
        textarea {
            width: 100%;
            box-sizing: border-box;
            background: #020617;
            color: #e5e7eb;
            border: 1px solid #4b5563;
            border-radius: 8px;
            padding: 10px;
            font-family: monospace;
        }

        textarea { min-height: 320px; }

        button {
            margin-top: 12px;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            cursor: pointer;
        }

        .tools-box button {
            margin-top: 0;
            padding: 7px 10px;
            font-size: 12px;
            background: #1d4ed8;
        }

        button.secondary { background: #475569; }
        button.danger { background: #b91c1c; }
        button.warning { background: #b45309; }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .muted {
            color: #9ca3af;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <form method="post" id="tinkerForm">
        <input type="hidden" name="output" value="<?= htmlspecialchars($output) ?>">
        <input type="hidden" name="log" value="<?= htmlspecialchars($log) ?>">

        <div class="page-header">
            <h1>Tinker Lab</h1>

            <div class="tools-box" aria-label="Herramientas Artisan">
                <span class="tools-box-label">Artisan</span>

                <button type="submit" name="artisan" value="migrate">
                    migrate
                </button>

                <button type="submit" name="artisan" value="migrate_fresh_seed" class="warning">
                    fresh --seed
                </button>

                <button type="submit" name="artisan" value="optimize_clear" class="secondary">
                    optimize:clear
                </button>

                <button type="submit" name="artisan" value="routes_metrics" class="secondary">
                    routes
                </button>
            </div>
        </div>

        <div class="grid">
            <div class="card full">
                <label>Caso</label>
                <input name="case" value="<?= htmlspecialchars($case) ?>" placeholder="orders-delete-approved">
            </div>

            <div class="card">
                <h3>Código Tinker</h3>
                <textarea name="code" id="code"><?= htmlspecialchars($code) ?></textarea>

                <div class="actions">
                    <button type="submit" name="run" value="1" id="runButton">Ejecutar Tinker</button>
                    <button type="submit" name="clear_all" value="1" class="danger">Borrar todo</button>
                    <button type="submit" name="close_session" value="1" class="secondary">Cerrar sesión</button>
                </div>
            </div>

            <div class="card">
                <h3>Salida</h3>
                <textarea readonly id="output"><?= htmlspecialchars($output) ?></textarea>

                <div class="actions">
                    <button type="button" onclick="copyField('output')">Copiar salida</button>
                    <button type="submit" name="clear_output" value="1" class="secondary">Borrar salida</button>
                </div>
            </div>

            <div class="card full">
                <h3>Log para chat</h3>
                <textarea readonly id="log"><?= htmlspecialchars($log) ?></textarea>

                <div class="actions">
                    <button type="button" onclick="copyField('log')">Copiar log para chat</button>
                    <button type="submit" name="clear_output" value="1" class="secondary">Limpiar log para chat</button>
                </div>
            </div>
        </div>
    </form>

    <br>
    <br>

    <div class="help">
        <strong>Ayuda rápida</strong>

        <p class="muted">Levantar servidor local desde la raíz del proyecto:</p>
        <code>php -S 127.0.0.1:8787 -t tools/tinker-lab</code>

        <p class="muted">Abrir la página:</p>
        <code>http://127.0.0.1:8787</code>

        <p class="muted">
            En el campo de código pegá solo el PHP interno,
            no el comando <code>php artisan tinker --execute</code>.
        </p>

        <p class="muted">
            Atajo: dentro del código Tinker, <strong>Enter ejecuta</strong>.
            Para salto de línea usá <strong>Shift + Enter</strong>.
        </p>

        <p class="muted">
            Log acumulativo:
            <code>documentos/log/tinker-lab.log</code>
        </p>
    </div>

    <script>
        function copyField(id) {
            const field = document.getElementById(id);
            field.select();
            document.execCommand('copy');
        }

        document.getElementById('code').addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                document.getElementById('runButton').click();
            }
        });
    </script>
</body>
</html>