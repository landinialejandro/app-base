<?php

// FILE: aplicar-actualizaciones-codigo.php | V3

declare(strict_types=1);

/**
 * Uso:
 *   php normalizar_codigo_version.php
 *
 * Luego pegar el contenido completo del archivo y finalizar con:
 *   Ctrl+D   (Linux/macOS)
 *   Ctrl+Z + Enter   (Windows)
 *
 * Comportamiento:
 * - Detecta archivo PHP o Blade
 * - Lee ruta desde encabezado FILE
 * - Si no tiene versión, agrega | V1
 * - Si ya tiene versión, la conserva
 * - Crea carpetas faltantes del archivo destino
 * - Guarda archivo en disco
 * - Informa estado por STDERR
 * - Registra log local en documentos/log/code-updates.log
 *
 * Recomendado en .gitignore:
 * documentos/log/code-updates.log
 */
final class CodeHeaderNormalizer
{
    private const LOG_PATH = 'documentos/log/code-updates.log';

    public function run(): int
    {
        $input = stream_get_contents(STDIN);

        if ($input === false || trim($input) === '') {
            fwrite(STDERR, "Error: no se recibió contenido por STDIN.\n");

            return 1;
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $input);

        if ($this->looksLikePhp($normalized)) {
            $result = $this->normalizePhp($normalized);

            if ($result === null) {
                fwrite(STDERR, "Error: no se pudo interpretar encabezado FILE para PHP.\n");

                return 1;
            }

            return $this->writeResult($result);
        }

        if ($this->looksLikeBlade($normalized)) {
            $result = $this->normalizeBlade($normalized);

            if ($result === null) {
                fwrite(STDERR, "Error: no se pudo interpretar encabezado FILE para Blade.\n");

                return 1;
            }

            return $this->writeResult($result);
        }

        fwrite(STDERR, "Error: no se detectó archivo PHP o Blade compatible.\n");

        return 1;
    }

    private function writeResult(array $result): int
    {
        $path = $result['path'];
        $version = $result['version'];
        $content = $result['content'];

        if ($path === '') {
            fwrite(STDERR, "Error: ruta FILE vacía.\n");

            return 1;
        }

        $directory = dirname($path);

        if ($directory !== '.' && ! is_dir($directory)) {
            if (! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                fwrite(STDERR, "Error: no se pudo crear carpeta {$directory}\n");

                return 1;
            }
        }

        $alreadyExists = file_exists($path);

        if (file_put_contents($path, $content) === false) {
            fwrite(STDERR, "Error: no se pudo escribir {$path}\n");

            return 1;
        }

        $status = $alreadyExists ? 'sobrescrito' : 'creado';

        fwrite(STDERR, "[OK] Archivo: {$path}\n");
        fwrite(STDERR, "[OK] Estado: {$status}\n");
        fwrite(STDERR, "[OK] Actualizado a: {$version}\n");

        $this->appendLog($path, $status, $version);

        return 0;
    }

    private function appendLog(string $path, string $status, string $version): void
    {
        $logDir = dirname(self::LOG_PATH);

        if (! is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $isNewFile = ! file_exists(self::LOG_PATH);

        $user = get_current_user();
        $host = php_uname('n');
        $date = date('Y-m-d H:i:s');

        $lines = '';

        if ($isNewFile) {
            $lines .= "ARCHIVO | FECHA | ESTADO | VERSION | USUARIO | HOST\n";
        }

        $lines .= "{$path} | {$date} | {$status} | {$version} | {$user} | {$host}\n";

        @file_put_contents(self::LOG_PATH, $lines, FILE_APPEND);
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

    /**
     * @return array{path:string, version:string, content:string}|null
     */
    private function normalizePhp(string $content): ?array
    {
        $pattern = '/^(<\?php\s*\n\s*\n)(\/\/\s*FILE:\s*([^\n|]+?)(\s*\|\s*V\d+)?\s*)$/m';

        if (! preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $fullMatch = $matches[0][0];
        $fullStart = $matches[0][1];

        $opening = $matches[1][0];
        $path = trim($matches[3][0]);
        $existingVersion = isset($matches[4][0]) ? trim($matches[4][0]) : '';

        $finalVersion = $existingVersion !== '' ? $existingVersion : '| V1';

        $replacement = $opening.'// FILE: '.$path.' '.$finalVersion;

        $newContent = substr($content, 0, $fullStart)
            .$replacement
            .substr($content, $fullStart + strlen($fullMatch));

        return [
            'path' => $path,
            'version' => ltrim($finalVersion, '| '),
            'content' => $newContent,
        ];
    }

    /**
     * @return array{path:string, version:string, content:string}|null
     */
    private function normalizeBlade(string $content): ?array
    {
        $pattern = '/^(\{\{\-\-\s*FILE:\s*([^\n|]+?))(\s*\|\s*V\d+)?(\s*\-\-\}\})$/m';

        if (! preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $fullMatch = $matches[0][0];
        $fullStart = $matches[0][1];

        $path = trim($matches[2][0]);
        $existingVersion = isset($matches[3][0]) ? trim($matches[3][0]) : '';

        $finalVersion = $existingVersion !== '' ? $existingVersion : '| V1';

        $replacement = '{{-- FILE: '.$path.' '.$finalVersion.' --}}';

        $newContent = substr($content, 0, $fullStart)
            .$replacement
            .substr($content, $fullStart + strlen($fullMatch));

        return [
            'path' => $path,
            'version' => ltrim($finalVersion, '| '),
            'content' => $newContent,
        ];
    }
}

exit((new CodeHeaderNormalizer)->run());
