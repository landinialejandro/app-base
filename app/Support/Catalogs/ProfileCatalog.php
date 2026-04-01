<?php

// FILE: app/Support/Catalogs/ProfileCatalog.php | V1

namespace App\Support\Catalogs;

class ProfileCatalog
{
    /**
     * Base mínima V2:
     * no se definen perfiles funcionales cerrados todavía
     * hasta validar la semántica real por rubro/puesto.
     */
    protected static array $definitions = [];

    public static function all(): array
    {
        return array_keys(static::$definitions);
    }

    public static function definitions(): array
    {
        return static::$definitions;
    }

    public static function exists(?string $slug): bool
    {
        if ($slug === null || $slug === '') {
            return false;
        }

        return array_key_exists($slug, static::$definitions);
    }

    public static function label(?string $slug, ?string $default = '—'): ?string
    {
        if ($slug === null) {
            return $default;
        }

        return static::$definitions[$slug]['label'] ?? $default;
    }

    public static function normalize(?string $slug): ?string
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return static::exists($slug) ? $slug : null;
    }
}
