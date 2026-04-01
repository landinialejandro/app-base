<?php

// FILE: app/Policies/AssetPolicy.php | V3

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;

class AssetPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->actionScope(
            ModuleCatalog::ASSETS,
            CapabilityCatalog::VIEW_ANY,
            app('tenant'),
            $user,
        ) !== false;
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->resolver()->actionScope(
            ModuleCatalog::ASSETS,
            CapabilityCatalog::VIEW,
            app('tenant'),
            $user,
        ) !== false;
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::ASSETS,
            CapabilityCatalog::CREATE,
            app('tenant'),
            $user,
        );
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::ASSETS,
            CapabilityCatalog::UPDATE,
            app('tenant'),
            $user,
        );
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::ASSETS,
            CapabilityCatalog::DELETE,
            app('tenant'),
            $user,
        );
    }
}
