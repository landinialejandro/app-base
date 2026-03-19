<?php

namespace App\Support\Auth;

use App\Models\Tenant;
use App\Support\Catalogs\ModuleCatalog;

class TenantModuleAccess
{
    protected static array $defaultModules = [
        ModuleCatalog::DASHBOARD => true,
        ModuleCatalog::PROJECTS => true,
        ModuleCatalog::TASKS => true,
        ModuleCatalog::PARTIES => true,
        ModuleCatalog::PRODUCTS => true,
        ModuleCatalog::ASSETS => true,
        ModuleCatalog::ORDERS => true,
        ModuleCatalog::DOCUMENTS => true,
    ];

    public static function enabledModules(?Tenant $tenant = null): array
    {
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);

        if (! $tenant) {
            return [];
        }

        $settings = $tenant->settings ?? [];
        $configured = $settings['enabled_modules'] ?? null;

        if (! is_array($configured)) {
            return static::$defaultModules;
        }

        return array_merge(static::$defaultModules, $configured);
    }

    public static function isEnabled(string $module, ?Tenant $tenant = null): bool
    {
        $modules = static::enabledModules($tenant);

        return (bool) ($modules[$module] ?? false);
    }
}
