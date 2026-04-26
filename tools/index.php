<?php

// FILE: tools/index.php | V3

declare(strict_types=1);

require_once __DIR__.'/lib/ProjectPaths.php';

ProjectPaths::chdirRoot();

if (! isLocalRequest()) {
    http_response_code(403);
    echo 'Acceso denegado. Esta herramienta solo debe ejecutarse localmente.';
    exit;
}

$activeTab = $_POST['tool_action'] ?? 'apply_code';
$input = $_POST['input'] ?? '';
$output = '';
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = match ($activeTab) {
        'apply_code' => runTool(['php', 'tools/aplicar-actualizaciones-codigo.php'], $input),
        'apply_docs' => runTool(['php', 'tools/aplicar-actualizaciones-docs.php'], $input),
        'run_tinker' => runTinker($input),
        default => [
            'ok' => false,
            'output' => '[ERROR] Acción no reconocida: '.var_export($activeTab, true),
        ],
    };

    $ok = $result['ok'];
    $output = $result['output'];
}

function isLocalRequest(): bool
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';

    return in_array($remote, ['127.0.0.1', '::1'], true);
}

function runTool(array $command, string $input): array
{
    if (trim($input) === '') {
        return [
            'ok' => false,
            'output' => '[ERROR] No se recibió contenido para ejecutar.',
        ];
    }

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, ProjectPaths::root());

    if (! is_resource($process)) {
        return [
            'ok' => false,
            'output' => '[ERROR] No se pudo iniciar el proceso.',
        ];
    }

    fwrite($pipes[0], $input);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]) ?: '';
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'ok' => $exitCode === 0,
        'output' => trim($stderr."\n".$stdout),
    ];
}

function runTinker(string $input): array
{
    if (trim($input) === '') {
        return [
            'ok' => false,
            'output' => '[ERROR] No se recibió código Tinker.',
        ];
    }

    $command = [
        'php',
        'artisan',
        'tinker',
        '--execute='.$input,
    ];

    return runTool($command, $input);
}

function selected(string $current, string $expected): string
{
    return $current === $expected ? 'active' : '';
}

function isActivePanel(string $current, string $expected): string
{
    return $current === $expected ? '' : 'hidden';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>app-base tools</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            color-scheme: light dark;
            --bg: #f5f5f4;
            --panel: #ffffff;
            --text: #1c1917;
            --muted: #78716c;
            --border: #d6d3d1;
            --accent: #2563eb;
            --accent-soft: #dbeafe;
            --danger: #dc2626;
            --success: #16a34a;
            --code-bg: #1e1e1e;
            --code-text: #e7e5e4;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0c0a09;
                --panel: #1c1917;
                --text: #f5f5f4;
                --muted: #a8a29e;
                --border: #44403c;
                --accent: #60a5fa;
                --accent-soft: #1e3a8a;
                --danger: #f87171;
                --success: #4ade80;
                --code-bg: #09090b;
                --code-text: #f5f5f4;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .app {
            min-height: 100vh;
            padding: 24px;
        }

        .shell {
            max-width: 1280px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 18px;
        }

        h1 {
            margin: 0;
            font-size: 24px;
        }

        .subtitle {
            color: var(--muted);
            margin-top: 6px;
            font-size: 14px;
        }

        .tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .tab-button {
            border: 1px solid var(--border);
            background: var(--panel);
            color: var(--text);
            padding: 10px 14px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .tab-button.active {
            border-color: var(--accent);
            background: var(--accent-soft);
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 16px;
        }

        .panel[hidden] {
            display: none;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }

        textarea {
            width: 100%;
            min-height: 540px;
            resize: vertical;
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 14px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            line-height: 1.45;
            background: var(--code-bg);
            color: var(--code-text);
        }

        pre {
            margin: 0;
            min-height: 540px;
            max-height: 680px;
            overflow: auto;
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 14px;
            background: var(--code-bg);
            color: var(--code-text);
            white-space: pre-wrap;
            font-size: 13px;
            line-height: 1.45;
        }

        .toolbar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        button {
            border: 1px solid var(--border);
            background: var(--panel);
            color: var(--text);
            padding: 9px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        button.primary {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        button.danger {
            color: var(--danger);
        }

        .status {
            margin-top: 12px;
            font-size: 13px;
            color: var(--muted);
        }

        .status.ok { color: var(--success); }
        .status.error { color: var(--danger); }

        .hint {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
<div class="app">
    <div class="shell">
        <header>
            <h1>app-base tools</h1>
            <div class="subtitle">Consola local para código, documentación y Tinker.</div>
        </header>

        <nav class="tabs">
            <button type="button" class="tab-button <?= selected($activeTab, 'apply_code') ?>" data-tab="apply_code">Código</button>
            <button type="button" class="tab-button <?= selected($activeTab, 'apply_docs') ?>" data-tab="apply_docs">Documentos</button>
            <button type="button" class="tab-button <?= selected($activeTab, 'run_tinker') ?>" data-tab="run_tinker">Tinker</button>
        </nav>

        <form method="post" id="form-apply_code" class="panel" <?= isActivePanel($activeTab, 'apply_code') ?>>
            <input type="hidden" name="tool_action" value="apply_code">
            <div class="hint">Acepta archivos completos PHP/Blade o reemplazos/altas parciales de métodos.</div>
            <div class="toolbar">
                <button type="submit" class="primary">Aplicar código</button>
                <button type="button" data-paste="input-apply_code">Pegar</button>
                <button type="button" data-copy="output-apply_code">Copiar salida</button>
                <button type="button" class="danger" data-clear="input-apply_code" data-output-clear="output-apply_code">Limpiar</button>
            </div>
            <div class="grid">
                <textarea id="input-apply_code" name="input" placeholder="Pegá aquí el código compatible con el automatizador..."><?= $activeTab === 'apply_code' ? e($input) : '' ?></textarea>
                <pre id="output-apply_code"><?= $activeTab === 'apply_code' ? e($output) : '' ?></pre>
            </div>
            <?php if ($activeTab === 'apply_code' && $ok !== null) { ?>
                <div class="status <?= $ok ? 'ok' : 'error' ?>"><?= $ok ? 'Finalizado correctamente.' : 'Finalizado con errores.' ?></div>
            <?php } ?>
        </form>

        <form method="post" id="form-apply_docs" class="panel" <?= isActivePanel($activeTab, 'apply_docs') ?>>
            <input type="hidden" name="tool_action" value="apply_docs">
            <div class="hint">Acepta bloques REEMPLAZAR EN / NUEVA SECCIÓN PROPUESTA.</div>
            <div class="toolbar">
                <button type="submit" class="primary">Aplicar documentos</button>
                <button type="button" data-paste="input-apply_docs">Pegar</button>
                <button type="button" data-copy="output-apply_docs">Copiar salida</button>
                <button type="button" class="danger" data-clear="input-apply_docs" data-output-clear="output-apply_docs">Limpiar</button>
            </div>
            <div class="grid">
                <textarea id="input-apply_docs" name="input" placeholder="Pegá aquí las secciones documentales..."><?= $activeTab === 'apply_docs' ? e($input) : '' ?></textarea>
                <pre id="output-apply_docs"><?= $activeTab === 'apply_docs' ? e($output) : '' ?></pre>
            </div>
            <?php if ($activeTab === 'apply_docs' && $ok !== null) { ?>
                <div class="status <?= $ok ? 'ok' : 'error' ?>"><?= $ok ? 'Finalizado correctamente.' : 'Finalizado con errores.' ?></div>
            <?php } ?>
        </form>

        <form method="post" id="form-run_tinker" class="panel" <?= isActivePanel($activeTab, 'run_tinker') ?>>
            <input type="hidden" name="tool_action" value="run_tinker">
            <div class="hint">Pegá solo código PHP interno de Tinker. No pegues php artisan tinker --execute.</div>
            <div class="toolbar">
                <button type="submit" class="primary">Ejecutar Tinker</button>
                <button type="button" data-paste="input-run_tinker">Pegar</button>
                <button type="button" data-copy="output-run_tinker">Copiar salida</button>
                <button type="button" class="danger" data-clear="input-run_tinker" data-output-clear="output-run_tinker">Limpiar</button>
            </div>
            <div class="grid">
                <textarea id="input-run_tinker" name="input" placeholder="use App\Models\User;&#10;&#10;$user = User::where('email', 'juan@tech.local')->first();&#10;dump($user?->email);"><?= $activeTab === 'run_tinker' ? e($input) : '' ?></textarea>
                <pre id="output-run_tinker"><?= $activeTab === 'run_tinker' ? e($output) : '' ?></pre>
            </div>
            <?php if ($activeTab === 'run_tinker' && $ok !== null) { ?>
                <div class="status <?= $ok ? 'ok' : 'error' ?>"><?= $ok ? 'Finalizado correctamente.' : 'Finalizado con errores.' ?></div>
            <?php } ?>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('[data-tab]').forEach(button => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tab;

            document.querySelectorAll('.tab-button').forEach(item => item.classList.remove('active'));
            button.classList.add('active');

            document.querySelectorAll('form.panel').forEach(panel => panel.hidden = true);
            document.getElementById('form-' + tab).hidden = false;
        });
    });

    document.querySelectorAll('[data-paste]').forEach(button => {
        button.addEventListener('click', async () => {
            const input = document.getElementById(button.dataset.paste);

            try {
                input.value = await navigator.clipboard.readText();
                input.focus();
            } catch (error) {
                input.focus();
input.select();

alert('El navegador bloqueó el portapapeles automático. Pegá manualmente con Ctrl + V.');
            }
        });
    });

    document.querySelectorAll('[data-copy]').forEach(button => {
        button.addEventListener('click', async () => {
            const output = document.getElementById(button.dataset.copy);

            try {
                await navigator.clipboard.writeText(output.textContent);
            } catch (error) {
                alert('No se pudo copiar la salida.');
            }
        });
    });

    document.querySelectorAll('[data-clear]').forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById(button.dataset.clear).value = '';
            document.getElementById(button.dataset.outputClear).textContent = '';
        });
    });
</script>
</body>
</html>