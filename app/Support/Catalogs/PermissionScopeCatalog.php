<?php

// FILE: app/Support/Catalogs/PermissionScopeCatalog.php | V4

namespace App\Support\Catalogs;

class PermissionScopeCatalog
{
    public const TENANT_ALL = 'tenant_all';

    public const OWN_ASSIGNED = 'own_assigned';

    public const LIMITED = 'limited';

    protected static array $labels = [
        self::TENANT_ALL => 'Toda la empresa',
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

    public static function optionsFor(string $module, string $capability): array
    {
        return match ($module) {
            ModuleCatalog::TASKS => static::taskOptionsFor($capability),
            ModuleCatalog::APPOINTMENTS => static::appointmentOptionsFor($capability),
            ModuleCatalog::PROJECTS => static::projectOptionsFor($capability),
            ModuleCatalog::PARTIES,
            ModuleCatalog::ASSETS,
            ModuleCatalog::PRODUCTS,
            ModuleCatalog::DOCUMENTS,
            ModuleCatalog::ORDERS => static::sharedModuleOptionsFor($capability),
            default => [],
        };
    }

    public static function supports(string $module, string $capability): bool
    {
        return ! empty(static::optionsFor($module, $capability));
    }

    protected static function taskOptionsFor(string $capability): array
    {
        return match ($capability) {
            CapabilityCatalog::VIEW_ANY => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
            ],
            CapabilityCatalog::VIEW => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
            ],
            CapabilityCatalog::UPDATE => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
                self::OWN_ASSIGNED => static::label(self::OWN_ASSIGNED),
            ],
            CapabilityCatalog::DELETE => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
            ],
            default => [],
        };
    }

    protected static function appointmentOptionsFor(string $capability): array
    {
        return match ($capability) {
            CapabilityCatalog::VIEW_ANY => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
            ],
            CapabilityCatalog::VIEW => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
                self::OWN_ASSIGNED => static::label(self::OWN_ASSIGNED),
            ],
            CapabilityCatalog::UPDATE => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
                self::OWN_ASSIGNED => static::label(self::OWN_ASSIGNED),
            ],
            CapabilityCatalog::DELETE => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
            ],
            default => [],
        };
    }

    protected static function projectOptionsFor(string $capability): array
    {
        return match ($capability) {
            CapabilityCatalog::VIEW_ANY => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
            ],
            CapabilityCatalog::VIEW => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
                self::LIMITED => static::label(self::LIMITED),
            ],
            CapabilityCatalog::UPDATE => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
                self::LIMITED => static::label(self::LIMITED),
            ],
            CapabilityCatalog::DELETE => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
            ],
            default => [],
        };
    }

    protected static function sharedModuleOptionsFor(string $capability): array
    {
        return match ($capability) {
            CapabilityCatalog::VIEW_ANY,
            CapabilityCatalog::VIEW,
            CapabilityCatalog::UPDATE,
            CapabilityCatalog::DELETE => [
                self::TENANT_ALL => static::label(self::TENANT_ALL),
            ],
            default => [],
        };
    }
}
