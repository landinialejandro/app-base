<?php

// FILE: app/Support/Catalogs/TenantCapabilityCatalog.php | V1

namespace App\Support\Catalogs;

class TenantCapabilityCatalog
{
    public const OPERATIONAL_ACTIVITY = 'operational_activity';

    protected static array $definitions = [
        self::OPERATIONAL_ACTIVITY => [
            'label' => 'Actividad operativa',
            'capabilities' => [
                CapabilityCatalog::VIEW_ANY,
            ],
        ],
    ];

    public static function all(): array
    {
        return array_keys(static::$definitions);
    }

    public static function contains(string $group): bool
    {
        return in_array($group, static::all(), true);
    }

    public static function label(?string $group, ?string $default = '—'): ?string
    {
        if ($group === null) {
            return $default;
        }

        return static::$definitions[$group]['label'] ?? $default;
    }

    public static function capabilitiesFor(string $group): array
    {
        return static::$definitions[$group]['capabilities'] ?? [];
    }
}