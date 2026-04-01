<?php

// FILE: app/Support/Catalogs/CapabilityCatalog.php | V1

namespace App\Support\Catalogs;

class CapabilityCatalog
{
    public const VIEW_ANY = 'view_any';

    public const VIEW = 'view';

    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    public const VIEW_ANALYTICS = 'view_analytics';

    protected static array $labels = [
        self::VIEW_ANY => 'Ver listado',
        self::VIEW => 'Ver',
        self::CREATE => 'Crear',
        self::UPDATE => 'Actualizar',
        self::DELETE => 'Eliminar',
        self::VIEW_ANALYTICS => 'Ver analíticas',
    ];

    public static function all(): array
    {
        return array_keys(static::$labels);
    }

    public static function labels(): array
    {
        return static::$labels;
    }

    public static function label(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$labels[$value] ?? $default;
    }

    public static function permissionSlug(string $module, string $capability): string
    {
        return $module.'.'.$capability;
    }

    public static function parsePermissionSlug(string $slug): ?array
    {
        $parts = explode('.', $slug, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$module, $capability] = $parts;

        if (! in_array($capability, static::all(), true)) {
            return null;
        }

        return [
            'module' => $module,
            'capability' => $capability,
        ];
    }
}
