<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Auth\RoleModuleAccess;
use App\Support\Auth\TenantAccess;
use App\Support\Catalogs\ModuleCatalog;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse(ModuleCatalog::PRODUCTS, $tenant, $user);
    }

    public function view(User $user, Product $product): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::PRODUCTS, $tenant, $user)) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse(ModuleCatalog::PRODUCTS, $tenant, $user);
    }

    public function update(User $user, Product $product): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::PRODUCTS, $tenant, $user)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Product $product): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::PRODUCTS, $tenant, $user)) {
            return false;
        }

        return TenantAccess::isOwnerOrAdmin($tenant->id, $user);
    }
}
