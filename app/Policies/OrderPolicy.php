<?php

// FILE: app/Policies/OrderPolicy.php | V3

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;

class OrderPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        $scope = $this->resolver()->actionScope(ModuleCatalog::ORDERS, CapabilityCatalog::VIEW_ANY, app('tenant'), $user);

        return in_array($scope, [true, 'tenant_all', 'all'], true);
    }

    public function view(User $user, Order $order): bool
    {
        $scope = $this->resolver()->actionScope(ModuleCatalog::ORDERS, CapabilityCatalog::VIEW, app('tenant'), $user);

        return in_array($scope, [true, 'tenant_all', 'all'], true);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(ModuleCatalog::ORDERS, CapabilityCatalog::CREATE, app('tenant'), $user);
    }

    public function update(User $user, Order $order): bool
    {
        $scope = $this->resolver()->actionScope(ModuleCatalog::ORDERS, CapabilityCatalog::UPDATE, app('tenant'), $user);

        if (in_array($scope, [true, 'tenant_all', 'all'], true)) {
            return true;
        }

        if ($scope === 'own_assigned') {
            return false;
        }

        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->resolver()->can(ModuleCatalog::ORDERS, CapabilityCatalog::DELETE, app('tenant'), $user);
    }
}
