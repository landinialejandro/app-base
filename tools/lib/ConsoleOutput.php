<?php

// FILE: tools/lib/ConsoleOutput.php | V1

declare(strict_types=1);

final class ConsoleOutput
{
    private bool $supportsColor;

    public function __construct()
    {
        $this->supportsColor = $this->detectColorSupport();
    }

    public function ok(string $message): void
    {
        $this->write('OK', $message, '32');
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message, '31');
    }

    public function warn(string $message): void
    {
        $this->write('WARN', $message, '33');
    }

    public function info(string $message): void
    {
        $this->write('INFO', $message, '36');
    }

    private function write(string $level, string $message, string $color): void
    {
        $label = "[{$level}]";

        if ($this->supportsColor) {
            $label = "\033[{$color}m{$label}\033[0m";
        }

        fwrite(STDERR, "{$label} {$message}\n");
    }

    private function detectColorSupport(): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        if (function_exists('stream_isatty')) {
            return stream_isatty(STDERR);
        }

        if (function_exists('posix_isatty')) {
            return posix_isatty(STDERR);
        }

        return false;
    }
}