<?php

// FILE: app/Support/Products/OperationalSummary/ProductOperationalSummaryAccess.php | V1

namespace App\Support\Products\OperationalSummary;

use App\Models\User;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\RoleCatalog;

class ProductOperationalSummaryAccess
{
    public function resolve(User $user): array
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if (! $tenant) {
            return $this->closed();
        }

        if (! TenantModuleAccess::isEnabled(ModuleCatalog::PRODUCTS, $tenant)) {
            return $this->closed();
        }

        if (! TenantModuleAccess::isEnabled(ModuleCatalog::INVENTORY, $tenant)) {
            return $this->closed();
        }

        $membership = $user->memberships()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('roles')
            ->first();

        if (! $membership) {
            return $this->closed();
        }

        $roleSlugs = $membership->roles
            ->pluck('slug')
            ->map(fn ($slug) => (string) $slug)
            ->values()
            ->all();

        $isOwner = (bool) $membership->is_owner;
        $isAdmin = in_array(RoleCatalog::ADMIN, $roleSlugs, true);
        $isAdministrator = in_array(RoleCatalog::ADMINISTRATOR, $roleSlugs, true);
        $isSales = in_array(RoleCatalog::SALES, $roleSlugs, true);

        $ordersEnabled = TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant);

        if ($isOwner || $isAdmin) {
            return [
                'can_view' => true,
                'can_view_purchases' => $ordersEnabled,
                'can_view_sales' => $ordersEnabled,
                'can_view_inventory' => true,
                'orders_enabled' => $ordersEnabled,
            ];
        }

        return [
            'can_view' => $isAdministrator || $isSales,
            'can_view_purchases' => $ordersEnabled && $isAdministrator,
            'can_view_sales' => $ordersEnabled && $isSales,
            'can_view_inventory' => $isAdministrator,
            'orders_enabled' => $ordersEnabled,
        ];
    }

    protected function closed(): array
    {
        return [
            'can_view' => false,
            'can_view_purchases' => false,
            'can_view_sales' => false,
            'can_view_inventory' => false,
            'orders_enabled' => false,
        ];
    }
}