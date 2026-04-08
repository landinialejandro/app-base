<?php

// FILE: app/Support/Auth/TenantModuleAccess.php | V3

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
        if ($module === ModuleCatalog::PARTIES) {
            return true;
        }

        $modules = static::enabledModules($tenant);

        return (bool) ($modules[$module] ?? false);
    }

    protected static function baseModules(): array
    {
        return collect(ModuleCatalog::all())
            ->mapWithKeys(function (string $module) {
                return [
                    $module => $module === ModuleCatalog::PARTIES ? true : true,
                ];
            })
            ->all();
    }

    protected static function globalOverrides(): array
    {
        $overrides = config('tenant_module_access.global', []);

        return is_array($overrides)
            ? static::filterValidModuleMap($overrides)
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
            $overrides = static::mergeModuleMap(
                $overrides,
                static::filterValidModuleMap($allTenantOverrides[$tenant->id])
            );
        }

        if (isset($tenant->slug) && isset($allTenantOverrides[$tenant->slug]) && is_array($allTenantOverrides[$tenant->slug])) {
            $overrides = static::mergeModuleMap(
                $overrides,
                static::filterValidModuleMap($allTenantOverrides[$tenant->slug])
            );
        }

        return $overrides;
    }

    protected static function tenantSettingOverrides(Tenant $tenant): array
    {
        $settings = $tenant->settings ?? [];

        if (! is_array($settings)) {
            return [];
        }

        $legacyOverrides = $settings['enabled_modules'] ?? [];
        $nestedOverrides = data_get($settings, 'module_access.enabled_modules', []);

        $overrides = [];

        if (is_array($legacyOverrides)) {
            $overrides = static::mergeModuleMap($overrides, static::filterValidModuleMap($legacyOverrides));
        }

        if (is_array($nestedOverrides)) {
            $overrides = static::mergeModuleMap($overrides, static::filterValidModuleMap($nestedOverrides));
        }

        return $overrides;
    }

    protected static function mergeModuleMap(array $base, array $overrides): array
    {
        foreach ($overrides as $module => $enabled) {
            if (! in_array($module, ModuleCatalog::all(), true)) {
                continue;
            }

            if ($module === ModuleCatalog::PARTIES) {
                $base[$module] = true;

                continue;
            }

            $base[$module] = (bool) $enabled;
        }

        return $base;
    }

    protected static function filterValidModuleMap(array $modules): array
    {
        $filtered = [];

        foreach ($modules as $module => $enabled) {
            if (! in_array($module, ModuleCatalog::all(), true)) {
                continue;
            }

            if ($module === ModuleCatalog::PARTIES) {
                $filtered[$module] = true;

                continue;
            }

            $filtered[$module] = (bool) $enabled;
        }

        return $filtered;
    }

    protected static function normalizeModuleMap(array $modules): array
    {
        $normalized = [];

        foreach (ModuleCatalog::all() as $module) {
            $normalized[$module] = $module === ModuleCatalog::PARTIES
                ? true
                : (bool) ($modules[$module] ?? false);
        }

        return $normalized;
    }
}
