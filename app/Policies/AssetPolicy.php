<?php

// FILE: app/Policies/AssetPolicy.php | V2

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;

class AssetPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::ASSETS, app('tenant'), $user);
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::ASSETS, app('tenant'), $user);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(ModuleCatalog::ASSETS, 'create', app('tenant'), $user);
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->resolver()->can(ModuleCatalog::ASSETS, 'update', app('tenant'), $user);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $this->resolver()->can(ModuleCatalog::ASSETS, 'delete', app('tenant'), $user);
    }
}
