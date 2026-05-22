<?php

namespace App\Support\Catalogs;

abstract class BaseCatalog
{
    protected static array $kinds = [];
    protected static array $statuses = [];
    protected static array $labels = [];
    protected static array $badges = [];
    protected static array $statusIntentions = [];
    protected static array $statusContextLabels = [];

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

    public static function statusIntention(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return static::$statusIntentions[$value] ?? $value;
    }

    public static function statusLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (array_key_exists($value, static::$statusContextLabels)) {
            return static::$statusContextLabels[$value];
        }

        if (array_key_exists($value, static::$statuses)) {
            return static::$statuses[$value];
        }

        if (static::$statusIntentions !== []) {
            return StatusVocabulary::label(static::statusIntention($value), $default);
        }

        return $default;
    }

    public static function badgeClass(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (array_key_exists($value, static::$badges)) {
            return static::$badges[$value];
        }

        if (static::$statusIntentions !== []) {
            return StatusVocabulary::badgeClass(static::statusIntention($value), '');
        }

        return '';
    }
}
