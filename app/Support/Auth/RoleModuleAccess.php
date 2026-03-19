<?php

namespace App\Support\Auth;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\RoleCatalog;

class RoleModuleAccess
{
    protected static function roleModules(): array
    {
        $standardModules = [
            ModuleCatalog::DASHBOARD,
            ModuleCatalog::PROJECTS,
            ModuleCatalog::TASKS,
            ModuleCatalog::PARTIES,
            ModuleCatalog::PRODUCTS,
            ModuleCatalog::ASSETS,
            ModuleCatalog::ORDERS,
            ModuleCatalog::DOCUMENTS,
        ];

        return [
            RoleCatalog::OWNER => ModuleCatalog::all(),
            RoleCatalog::ADMIN => ModuleCatalog::all(),
            RoleCatalog::SALES => $standardModules,
            RoleCatalog::OPERATOR => $standardModules,
        ];
    }

    public static function canUse(string $module, ?Tenant $tenant = null, ?User $user = null): bool
    {
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);
        $user = $user ?: auth()->user();

        if (! $tenant || ! $user) {
            return false;
        }

        if (! TenantModuleAccess::isEnabled($module, $tenant)) {
            return false;
        }

        $tenantId = $tenant->id;

        if (TenantAccess::isOwnerOrAdmin($tenantId, $user)) {
            return true;
        }

        $roleModules = static::roleModules();
        $roleSlugs = TenantAccess::roleSlugs($tenantId, $user);

        foreach ($roleSlugs as $roleSlug) {
            if (in_array($module, $roleModules[$roleSlug] ?? [], true)) {
                return true;
            }
        }

        return false;
    }
}
