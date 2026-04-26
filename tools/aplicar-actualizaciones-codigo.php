<?php

// FILE: tools/aplicar-actualizaciones-codigo.php | V6

declare(strict_types=1);

require_once __DIR__.'/lib/ConsoleOutput.php';
require_once __DIR__.'/lib/ProjectPaths.php';
require_once __DIR__.'/lib/ToolLog.php';

/**
 * Automatizador de actualizaciones de código por STDIN.
 *
 * Soporta:
 *
 * 1) Archivo PHP completo:
 *   <?php
 *   // FILE: app/Support/Ejemplo.php | V1
 *
 * 2) Archivo Blade completo:
 *   {{-- FILE: resources/views/modulo/vista.blade.php | V1 --}}
 *
 * 3) Reemplazo de método PHP existente:
 *   // TARGET: app/Http/Controllers/EjemploController.php :: show
 *
 * 4) Alta de método PHP nuevo:
 *   // TARGET: app/Support/Ejemplo.php ++ nuevoMetodo
 *
 * Registra cada cambio en:
 *   documentos/log/code-updates.log
 */
ProjectPaths::chdirRoot();

final class CodeHeaderNormalizer
{
    private const LOG_FILE = 'code-updates.log';

    private ConsoleOutput $console;

    public function __construct()
    {
        $this->console = new ConsoleOutput;
    }

    public function run(): int
    {
        $input = stream_get_contents(STDIN);

        if ($input === false || trim($input) === '') {
            $this->console->error('No se recibió contenido por STDIN.');

            return 1;
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $input);

        $methodOperation = $this->tryParsePhpMethodOperation($normalized);

        if ($methodOperation !== null) {
            if ($methodOperation['operation'] === 'replace') {
                return $this->applyPhpMethodPatch($methodOperation);
            }

            if ($methodOperation['operation'] === 'add') {
                return $this->applyPhpMethodAdd($methodOperation);
            }

            $this->console->error('Operación de método no soportada.');

            return 1;
        }

        if ($this->looksLikePhp($normalized)) {
            $result = $this->normalizePhp($normalized);

            if ($result === null) {
                $this->console->error('No se pudo interpretar encabezado FILE para PHP.');

                return 1;
            }

            return $this->writeResult($result);
        }

        if ($this->looksLikeBlade($normalized)) {
            $result = $this->normalizeBlade($normalized);

            if ($result === null) {
                $this->console->error('No se pudo interpretar encabezado FILE para Blade.');

                return 1;
            }

            return $this->writeResult($result);
        }

        $this->console->error('No se detectó un formato compatible.');
        $this->console->info('Formatos válidos: archivo completo PHP, archivo completo Blade, parche parcial de método PHP o alta de método PHP.');

        return 1;
    }

    private function writeResult(array $result): int
    {
        $path = $result['path'];
        $version = $result['version'];
        $content = $result['content'];
        $mode = $result['mode'];
        $status = $result['status'];
        $target = $result['target'];

        if ($path === '') {
            $this->console->error('Ruta vacía.');

            return 1;
        }

        if ($mode !== 'php_method_patch' && $mode !== 'php_method_add') {
            $isSuspiciousPath =
                strlen($path) < 3
                || (! str_contains($path, '/') && ! str_contains($path, '.'));

            if ($isSuspiciousPath) {
                $this->console->error("Ruta destino sospechosa: {$path}");

                return 1;
            }
        }

        if (($mode === 'php_method_patch' || $mode === 'php_method_add') && ! file_exists($path)) {
            $this->console->error("El archivo destino no existe: {$path}");

            return 1;
        }

        $directory = dirname($path);

        if ($mode !== 'php_method_patch' && $mode !== 'php_method_add' && $directory !== '.' && ! ProjectPaths::ensureDirectory($directory)) {
            $this->console->error("No se pudo crear la carpeta: {$directory}");

            return 1;
        }

        if (file_put_contents($path, $content) === false) {
            $this->console->error("No se pudo escribir el archivo: {$path}");

            return 1;
        }

        $this->console->ok("Modo: {$mode}");
        $this->console->ok("Archivo: {$path}");
        $this->console->ok("Estado: {$status}");
        $this->console->ok("Objetivo: {$target}");

        if ($version !== '-') {
            $this->console->ok("Versión: {$version}");
        }

        $this->appendLog($path, $status, $version, $mode, $target);

        return 0;
    }

    private function appendLog(
        string $path,
        string $status,
        string $version,
        string $mode,
        string $target
    ): void {
        ToolLog::append(
            ProjectPaths::logPath(self::LOG_FILE),
            ['ARCHIVO', 'FECHA', 'ESTADO', 'VERSION', 'MODO', 'OBJETIVO', 'USUARIO', 'HOST'],
            [
                $path,
                ToolLog::now(),
                $status,
                $version,
                $mode,
                $target,
                ToolLog::currentUser(),
                ToolLog::currentHost(),
            ]
        );
    }

    private function looksLikePhp(string $content): bool
    {
        return str_starts_with(ltrim($content), '<?php');
    }

    private function looksLikeBlade(string $content): bool
    {
        $trimmed = ltrim($content);

        return str_starts_with($trimmed, '{{-- FILE:')
            || str_contains($content, '@extends')
            || str_contains($content, '@section')
            || str_contains($content, '<x-page');
    }

    private function normalizePhp(string $content): ?array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $trimmed = ltrim($content);

        if (! str_starts_with($trimmed, '<?php')) {
            return null;
        }

        $afterOpen = substr($trimmed, 5);
        $afterOpen = ltrim($afterOpen, "\n");

        $lines = explode("\n", $afterOpen);
        $headerLine = $lines[0] ?? '';

        if (! preg_match('/^\/\/\s*FILE:\s*(.+?)(?:\s*\|\s*(V\d+))?\s*$/', trim($headerLine), $matches)) {
            return null;
        }

        $path = trim($matches[1] ?? '');
        $existingVersion = trim($matches[2] ?? '');

        if ($path === '') {
            return null;
        }

        $finalVersion = $existingVersion !== '' ? $existingVersion : 'V1';

        array_shift($lines);
        $body = ltrim(implode("\n", $lines), "\n");

        $newContent = "<?php\n\n// FILE: {$path} | {$finalVersion}\n\n".$body;

        return [
            'path' => $path,
            'version' => $finalVersion,
            'content' => $newContent,
            'mode' => 'php_full',
            'status' => file_exists($path) ? 'sobrescrito' : 'creado',
            'target' => 'archivo_completo',
        ];
    }

    private function normalizeBlade(string $content): ?array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $trimmed = ltrim($content);

        $lines = explode("\n", $trimmed);
        $headerLine = $lines[0] ?? '';

        if (! preg_match('/^\{\{\-\-\s*FILE:\s*(.+?)(?:\s*\|\s*(V\d+))?\s*\-\-\}\}$/', trim($headerLine), $matches)) {
            return null;
        }

        $path = trim($matches[1] ?? '');
        $existingVersion = trim($matches[2] ?? '');

        if ($path === '') {
            return null;
        }

        $finalVersion = $existingVersion !== '' ? $existingVersion : 'V1';

        array_shift($lines);
        $body = ltrim(implode("\n", $lines), "\n");

        $newContent = "{{-- FILE: {$path} | {$finalVersion} --}}\n\n".$body;

        return [
            'path' => $path,
            'version' => $finalVersion,
            'content' => $newContent,
            'mode' => 'blade_full',
            'status' => file_exists($path) ? 'sobrescrito' : 'creado',
            'target' => 'archivo_completo',
        ];
    }

    private function tryParsePhpMethodOperation(string $content): ?array
    {
        $normalized = ltrim($content);

        if (str_starts_with($normalized, '<?php')) {
            $normalized = ltrim(substr($normalized, 5));
        }

        $replacePattern = '/^(?:\/\/\s*(?:TARGET|FILE):\s*([^\n:]+?\.php)\s*::\s*([A-Za-z_][A-Za-z0-9_]*)\s*)\n+/';
        $addPattern = '/^(?:\/\/\s*(?:TARGET|FILE):\s*([^\n+]+?\.php)\s*\+\+\s*([A-Za-z_][A-Za-z0-9_]*)\s*)\n+/';

        if (preg_match($replacePattern, $normalized, $matches, PREG_OFFSET_CAPTURE)) {
            $fullMatch = $matches[0][0];
            $path = trim($matches[1][0]);
            $method = trim($matches[2][0]);
            $snippet = ltrim(substr($normalized, strlen($fullMatch)), "\n");

            if ($path === '' || $method === '' || $snippet === '') {
                return null;
            }

            if (! preg_match('/function\s+'.preg_quote($method, '/').'\s*\(/', $snippet)) {
                return null;
            }

            return [
                'path' => $path,
                'method' => $method,
                'snippet' => rtrim($snippet)."\n",
                'operation' => 'replace',
            ];
        }

        if (preg_match($addPattern, $normalized, $matches, PREG_OFFSET_CAPTURE)) {
            $fullMatch = $matches[0][0];
            $path = trim($matches[1][0]);
            $method = trim($matches[2][0]);
            $snippet = ltrim(substr($normalized, strlen($fullMatch)), "\n");

            if ($path === '' || $method === '' || $snippet === '') {
                return null;
            }

            if (! preg_match('/function\s+'.preg_quote($method, '/').'\s*\(/', $snippet)) {
                return null;
            }

            return [
                'path' => $path,
                'method' => $method,
                'snippet' => rtrim($snippet)."\n",
                'operation' => 'add',
            ];
        }

        return null;
    }

    private function applyPhpMethodPatch(array $patch): int
    {
        $path = $patch['path'];
        $method = $patch['method'];
        $snippet = $patch['snippet'];

        if (! file_exists($path)) {
            $this->console->error("No existe el archivo destino para el método parcial: {$path}");

            return 1;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            $this->console->error("No se pudo leer el archivo destino: {$path}");

            return 1;
        }

        $bounds = $this->findPhpMethodBounds($content, $method);

        if ($bounds === null) {
            $this->console->error("No se encontró el método {$method} en {$path}");

            return 1;
        }

        $start = $bounds['start'];
        $end = $bounds['end'];

        $before = substr($content, 0, $start);
        $after = substr($content, $end);

        $before = rtrim($before, "\n");
        $after = ltrim($after, "\n");
        $normalizedSnippet = trim($snippet, "\n");

        $newContent = $before."\n\n".$normalizedSnippet."\n\n".$after;

        return $this->writeResult([
            'path' => $path,
            'version' => '-',
            'content' => $newContent,
            'mode' => 'php_method_patch',
            'status' => 'metodo_actualizado',
            'target' => $method,
        ]);
    }

    private function applyPhpMethodAdd(array $patch): int
    {
        $path = $patch['path'];
        $method = $patch['method'];
        $snippet = $patch['snippet'];

        if (! file_exists($path)) {
            $this->console->error("No existe el archivo destino para agregar método: {$path}");

            return 1;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            $this->console->error("No se pudo leer el archivo destino: {$path}");

            return 1;
        }

        if ($this->findPhpMethodBounds($content, $method) !== null) {
            $this->console->error("El método {$method} ya existe en {$path}");

            return 1;
        }

        $classEnd = $this->findPrimaryClassClosingBraceOffset($content);

        if ($classEnd === null) {
            $this->console->error("No se pudo ubicar el cierre de la clase principal en {$path}");

            return 1;
        }

        $before = rtrim(substr($content, 0, $classEnd), "\n");
        $after = ltrim(substr($content, $classEnd), "\n");
        $normalizedSnippet = trim($snippet, "\n");
        $indentedSnippet = $this->indentMethodSnippet($normalizedSnippet, 4);

        $newContent = $before."\n\n".$indentedSnippet."\n\n".$after;

        return $this->writeResult([
            'path' => $path,
            'version' => '-',
            'content' => $newContent,
            'mode' => 'php_method_add',
            'status' => 'metodo_agregado',
            'target' => $method,
        ]);
    }

    private function findPrimaryClassClosingBraceOffset(string $content): ?int
    {
        $tokens = token_get_all($content);
        $offset = 0;
        $indexedTokens = [];

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $text = $token[1];
                $indexedTokens[] = [
                    'id' => $token[0],
                    'text' => $text,
                    'offset' => $offset,
                ];
                $offset += strlen($text);
            } else {
                $indexedTokens[] = [
                    'id' => null,
                    'text' => $token,
                    'offset' => $offset,
                ];
                $offset += strlen($token);
            }
        }

        $count = count($indexedTokens);

        for ($i = 0; $i < $count; $i++) {
            if (! in_array($indexedTokens[$i]['id'], [T_CLASS, T_FINAL, T_ABSTRACT], true)) {
                continue;
            }

            if ($indexedTokens[$i]['id'] !== T_CLASS) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($indexedTokens[$j]['id'] === T_CLASS) {
                        $i = $j;
                        break;
                    }

                    if (! in_array($indexedTokens[$j]['id'], [T_WHITESPACE, T_FINAL, T_ABSTRACT], true)) {
                        break;
                    }
                }

                if (($indexedTokens[$i]['id'] ?? null) !== T_CLASS) {
                    continue;
                }
            }

            for ($j = $i + 1; $j < $count; $j++) {
                if ($indexedTokens[$j]['text'] === '{') {
                    return $this->findMatchingBraceStartOffsetForClass($content, $indexedTokens[$j]['offset']);
                }
            }
        }

        return null;
    }

    private function findMatchingBraceStartOffsetForClass(string $content, int $classBodyStartOffset): ?int
    {
        $length = strlen($content);
        $depth = 0;

        for ($i = $classBodyStartOffset; $i < $length; $i++) {
            $char = $content[$i];

            if ($char === '{') {
                $depth++;

                continue;
            }

            if ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    private function indentMethodSnippet(string $snippet, int $spaces = 4): string
    {
        $indent = str_repeat(' ', $spaces);
        $lines = explode("\n", $snippet);

        $minIndent = null;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            preg_match('/^(\s*)/', $line, $matches);
            $currentIndent = strlen($matches[1] ?? '');

            if ($minIndent === null || $currentIndent < $minIndent) {
                $minIndent = $currentIndent;
            }
        }

        $minIndent ??= 0;

        $normalized = array_map(function (string $line) use ($indent, $minIndent) {
            if (trim($line) === '') {
                return '';
            }

            return $indent.substr($line, $minIndent);
        }, $lines);

        return implode("\n", $normalized);
    }

    private function findPhpMethodBounds(string $content, string $methodName): ?array
    {
        $tokens = token_get_all($content);
        $offset = 0;
        $indexedTokens = [];

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $text = $token[1];
                $indexedTokens[] = [
                    'id' => $token[0],
                    'text' => $text,
                    'offset' => $offset,
                ];
                $offset += strlen($text);
            } else {
                $indexedTokens[] = [
                    'id' => null,
                    'text' => $token,
                    'offset' => $offset,
                ];
                $offset += strlen($token);
            }
        }

        $count = count($indexedTokens);

        for ($i = 0; $i < $count; $i++) {
            if ($indexedTokens[$i]['id'] !== T_FUNCTION) {
                continue;
            }

            $nameIndex = $this->findMethodNameTokenIndex($indexedTokens, $i);

            if ($nameIndex === null) {
                continue;
            }

            if ($indexedTokens[$nameIndex]['text'] !== $methodName) {
                continue;
            }

            $startOffset = $this->findMethodStartOffset($indexedTokens, $i);
            $bodyStartIndex = $this->findMethodBodyStartIndex($indexedTokens, $i);

            if ($bodyStartIndex === null) {
                return null;
            }

            $bodyStartOffset = $indexedTokens[$bodyStartIndex]['offset'];
            $bodyEndOffset = $this->findMatchingBraceEndOffset($content, $bodyStartOffset);

            if ($bodyEndOffset === null) {
                return null;
            }

            return [
                'start' => $startOffset,
                'end' => $bodyEndOffset,
            ];
        }

        return null;
    }

    private function findMethodNameTokenIndex(array $tokens, int $functionIndex): ?int
    {
        $count = count($tokens);

        for ($i = $functionIndex + 1; $i < $count; $i++) {
            $id = $tokens[$i]['id'];
            $text = $tokens[$i]['text'];

            if ($text === '(') {
                return null;
            }

            if ($id === T_STRING) {
                return $i;
            }
        }

        return null;
    }

    private function findMethodStartOffset(array $tokens, int $functionIndex): int
    {
        $startIndex = $functionIndex;

        for ($i = $functionIndex - 1; $i >= 0; $i--) {
            $id = $tokens[$i]['id'];

            if ($id === T_WHITESPACE) {
                if (substr_count($tokens[$i]['text'], "\n") >= 2) {
                    break;
                }

                $startIndex = $i;

                continue;
            }

            if ($id === T_DOC_COMMENT || $id === T_COMMENT) {
                $startIndex = $i;

                continue;
            }

            break;
        }

        $content = $this->tokensToContentSlice($tokens, 0, $startIndex);
        $lineStart = strrpos($content, "\n");

        if ($lineStart === false) {
            return 0;
        }

        return $lineStart + 1;
    }

    private function findMethodBodyStartIndex(array $tokens, int $functionIndex): ?int
    {
        $count = count($tokens);
        $parenDepth = 0;
        $seenParenthesis = false;

        for ($i = $functionIndex; $i < $count; $i++) {
            $text = $tokens[$i]['text'];

            if ($text === '(') {
                $parenDepth++;
                $seenParenthesis = true;

                continue;
            }

            if ($text === ')') {
                $parenDepth--;

                continue;
            }

            if ($seenParenthesis && $parenDepth === 0 && $text === '{') {
                return $i;
            }

            if ($seenParenthesis && $parenDepth === 0 && $text === ';') {
                return null;
            }
        }

        return null;
    }

    private function findMatchingBraceEndOffset(string $content, int $bodyStartOffset): ?int
    {
        $length = strlen($content);
        $depth = 0;

        for ($i = $bodyStartOffset; $i < $length; $i++) {
            $char = $content[$i];

            if ($char === '{') {
                $depth++;

                continue;
            }

            if ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return $i + 1;
                }
            }
        }

        return null;
    }

    private function tokensToContentSlice(array $tokens, int $startIndex, int $endIndex): string
    {
        $buffer = '';

        for ($i = $startIndex; $i <= $endIndex; $i++) {
            $buffer .= $tokens[$i]['text'];
        }

        return $buffer;
    }
}

exit((new CodeHeaderNormalizer)->run());
