<?php

// FILE: app/Support/Catalogs/PermissionScopeCatalog.php | V1

namespace App\Support\Catalogs;

class PermissionScopeCatalog
{
    public const TENANT_ALL = 'tenant_all';

    public const ALL = 'all';

    public const OWN_ASSIGNED = 'own_assigned';

    public const LIMITED = 'limited';

    protected static array $labels = [
        self::TENANT_ALL => 'Toda la empresa',
        self::ALL => 'Todos',
        self::OWN_ASSIGNED => 'Solo asignados al usuario',
        self::LIMITED => 'Limitado',
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
        if ($value === null || $value === '') {
            return $default;
        }

        return static::$labels[$value] ?? $default;
    }

    public static function optionsForCapability(string $capability): array
    {
        return match ($capability) {
            CapabilityCatalog::VIEW_ANY => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
                self::ALL => static::label(self::ALL),
                self::OWN_ASSIGNED => static::label(self::OWN_ASSIGNED),
                self::LIMITED => static::label(self::LIMITED),
            ],

            CapabilityCatalog::VIEW => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
                self::ALL => static::label(self::ALL),
                self::OWN_ASSIGNED => static::label(self::OWN_ASSIGNED),
                self::LIMITED => static::label(self::LIMITED),
            ],

            CapabilityCatalog::UPDATE => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
                self::ALL => static::label(self::ALL),
                self::OWN_ASSIGNED => static::label(self::OWN_ASSIGNED),
            ],

            default => [],
        };
    }

    public static function supportsCapability(string $capability): bool
    {
        return ! empty(static::optionsForCapability($capability));
    }
}
