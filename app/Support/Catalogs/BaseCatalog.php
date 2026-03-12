<?php

namespace App\Support\Catalogs;

abstract class BaseCatalog
{
    protected static array $kinds = [];
    protected static array $statuses = [];
    protected static array $labels = [];
    protected static array $badges = [];

    public static function kinds(): array
    {
        return array_keys(static::$kinds);
    }

    public static function statuses(): array
    {
        return array_keys(static::$statuses);
    }

    public static function kindLabels(): array
    {
        return static::$kinds;
    }

    public static function statusLabels(): array
    {
        return static::$statuses;
    }

    public static function label(?string $value): string
    {
        return static::$labels[$value]
            ?? static::$kinds[$value]
            ?? static::$statuses[$value]
            ?? (string) $value;
    }

    public static function badgeClass(?string $value): string
    {
        return static::$badges[$value] ?? '';
    }
}
