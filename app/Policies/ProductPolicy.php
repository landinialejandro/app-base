<?php

// FILE: app/Policies/ProductPolicy.php | V3

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;

class ProductPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->actionScope(
            ModuleCatalog::PRODUCTS,
            CapabilityCatalog::VIEW_ANY,
            app('tenant'),
            $user,
        ) !== false;
    }

    public function view(User $user, Product $product): bool
    {
        return $this->resolver()->actionScope(
            ModuleCatalog::PRODUCTS,
            CapabilityCatalog::VIEW,
            app('tenant'),
            $user,
        ) !== false;
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::PRODUCTS,
            CapabilityCatalog::CREATE,
            app('tenant'),
            $user,
        );
    }

    public function update(User $user, Product $product): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::PRODUCTS,
            CapabilityCatalog::UPDATE,
            app('tenant'),
            $user,
        );
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::PRODUCTS,
            CapabilityCatalog::DELETE,
            app('tenant'),
            $user,
        );
    }
}
