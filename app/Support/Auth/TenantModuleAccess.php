<?php

namespace App\Support\Auth;

use App\Models\Tenant;
use App\Support\Catalogs\ModuleCatalog;

class TenantModuleAccess
{
    public static function enabledModules(?Tenant $tenant = null): array
    {
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);

        if (! $tenant) {
            return [];
        }

        $modules = static::baseModules();

        $modules = static::mergeModuleMap($modules, static::globalOverrides());
        $modules = static::mergeModuleMap($modules, static::tenantConfigOverrides($tenant));
        $modules = static::mergeModuleMap($modules, static::tenantSettingOverrides($tenant));

        return static::normalizeModuleMap($modules);
    }

    public static function isEnabled(string $module, ?Tenant $tenant = null): bool
    {
        $modules = static::enabledModules($tenant);

        return (bool) ($modules[$module] ?? false);
    }

    protected static function baseModules(): array
    {
        return collect(ModuleCatalog::all())
            ->mapWithKeys(fn (string $module) => [$module => true])
            ->all();
    }

    protected static function globalOverrides(): array
    {
        $overrides = config('tenant_module_access.global', []);

        return is_array($overrides)
            ? static::normalizeModuleMap($overrides)
            : [];
    }

    protected static function tenantConfigOverrides(Tenant $tenant): array
    {
        $allTenantOverrides = config('tenant_module_access.tenants', []);

        if (! is_array($allTenantOverrides) || empty($allTenantOverrides)) {
            return [];
        }

        $overrides = [];

        if (isset($allTenantOverrides[$tenant->id]) && is_array($allTenantOverrides[$tenant->id])) {
            $overrides = static::mergeModuleMap($overrides, $allTenantOverrides[$tenant->id]);
        }

        if (isset($tenant->slug) && isset($allTenantOverrides[$tenant->slug]) && is_array($allTenantOverrides[$tenant->slug])) {
            $overrides = static::mergeModuleMap($overrides, $allTenantOverrides[$tenant->slug]);
        }

        return static::normalizeModuleMap($overrides);
    }

    protected static function tenantSettingOverrides(Tenant $tenant): array
    {
        $settings = $tenant->settings ?? [];

        if (! is_array($settings)) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad actual
        |--------------------------------------------------------------------------
        |
        | Se mantiene soporte a:
        | - settings['enabled_modules']
        |
        | Y se deja listo soporte futuro más explícito:
        | - settings['module_access']['enabled_modules']
        |
        */

        $legacyOverrides = $settings['enabled_modules'] ?? [];
        $nestedOverrides = data_get($settings, 'module_access.enabled_modules', []);

        $overrides = [];

        if (is_array($legacyOverrides)) {
            $overrides = static::mergeModuleMap($overrides, $legacyOverrides);
        }

        if (is_array($nestedOverrides)) {
            $overrides = static::mergeModuleMap($overrides, $nestedOverrides);
        }

        return static::normalizeModuleMap($overrides);
    }

    protected static function mergeModuleMap(array $base, array $overrides): array
    {
        foreach ($overrides as $module => $enabled) {
            if (! in_array($module, ModuleCatalog::all(), true)) {
                continue;
            }

            $base[$module] = (bool) $enabled;
        }

        return $base;
    }

    protected static function normalizeModuleMap(array $modules): array
    {
        $normalized = [];

        foreach (ModuleCatalog::all() as $module) {
            $normalized[$module] = (bool) ($modules[$module] ?? false);
        }

        return $normalized;
    }
}
