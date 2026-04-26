<?php

// FILE: tools/lib/ToolLog.php | V1

declare(strict_types=1);

final class ToolLog
{
    public static function append(string $logPath, array $columns, array $row): void
    {
        $logDir = dirname($logPath);

        if (! is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $isNewFile = ! file_exists($logPath);

        $lines = '';

        if ($isNewFile) {
            $lines .= implode(' | ', $columns)."\n";
        }

        $lines .= implode(' | ', $row)."\n";

        @file_put_contents($logPath, $lines, FILE_APPEND);
    }

    public static function currentUser(): string
    {
        return get_current_user() ?: 'unknown';
    }

    public static function currentHost(): string
    {
        return php_uname('n') ?: 'unknown';
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}