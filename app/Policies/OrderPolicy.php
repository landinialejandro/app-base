<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Support\Auth\RoleModuleAccess;
use App\Support\Auth\TenantAccess;
use App\Support\Catalogs\ModuleCatalog;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse(ModuleCatalog::ORDERS, $tenant, $user);
    }

    public function view(User $user, Order $order): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::ORDERS, $tenant, $user)) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse(ModuleCatalog::ORDERS, $tenant, $user);
    }

    public function update(User $user, Order $order): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::ORDERS, $tenant, $user)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Order $order): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::ORDERS, $tenant, $user)) {
            return false;
        }

        return TenantAccess::isOwnerOrAdmin($tenant->id, $user);
    }
}
