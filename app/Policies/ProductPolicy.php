<?php

// FILE: app/Policies/ProductPolicy.php | V2

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;

class ProductPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::PRODUCTS, app('tenant'), $user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::PRODUCTS, app('tenant'), $user);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(ModuleCatalog::PRODUCTS, 'create', app('tenant'), $user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->resolver()->can(ModuleCatalog::PRODUCTS, 'update', app('tenant'), $user);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->resolver()->can(ModuleCatalog::PRODUCTS, 'delete', app('tenant'), $user);
    }
}
