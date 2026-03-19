<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Support\Auth\RoleModuleAccess;
use App\Support\Auth\TenantAccess;
use App\Support\Catalogs\ModuleCatalog;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse(ModuleCatalog::ASSETS, $tenant, $user);
    }

    public function view(User $user, Asset $asset): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::ASSETS, $tenant, $user)) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse(ModuleCatalog::ASSETS, $tenant, $user);
    }

    public function update(User $user, Asset $asset): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::ASSETS, $tenant, $user)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Asset $asset): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::ASSETS, $tenant, $user)) {
            return false;
        }

        return TenantAccess::isOwnerOrAdmin($tenant->id, $user);
    }
}
