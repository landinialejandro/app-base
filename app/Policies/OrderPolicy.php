<?php

// FILE: app/Policies/OrderPolicy.php | V2

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Auth\TenantAccess;
use App\Support\Catalogs\ModuleCatalog;

class OrderPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::ORDERS, app('tenant'), $user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::ORDERS, app('tenant'), $user);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(ModuleCatalog::ORDERS, 'create', app('tenant'), $user);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->resolver()->can(ModuleCatalog::ORDERS, 'update', app('tenant'), $user);
    }

    public function delete(User $user, Order $order): bool
    {
        if (! $this->resolver()->canUseModule(ModuleCatalog::ORDERS, app('tenant'), $user)) {
            return false;
        }

        return TenantAccess::isOwnerOrAdmin(app('tenant')->id, $user);
    }
}
