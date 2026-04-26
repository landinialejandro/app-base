<?php

// FILE: tools/lib/ProjectPaths.php | V1

declare(strict_types=1);

final class ProjectPaths
{
    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function chdirRoot(): void
    {
        $root = self::root();

        if (is_dir($root)) {
            chdir($root);
        }
    }

    public static function documentos(): string
    {
        return self::root().DIRECTORY_SEPARATOR.'documentos';
    }

    public static function baks(): string
    {
        return self::documentos().DIRECTORY_SEPARATOR.'baks';
    }

    public static function logDir(): string
    {
        return self::documentos().DIRECTORY_SEPARATOR.'log';
    }

    public static function logPath(string $fileName): string
    {
        return self::logDir().DIRECTORY_SEPARATOR.$fileName;
    }

    public static function ensureDirectory(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, 0775, true) || is_dir($path);
    }
}