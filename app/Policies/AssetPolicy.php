<?php

// FILE: app/Policies/AssetPolicy.php | V4

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Support\Auth\RecordScopeResolver;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;

class AssetPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    protected function recordScopeResolver(): RecordScopeResolver
    {
        return app(RecordScopeResolver::class);
    }

    public function viewAny(User $user): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::ASSETS,
            CapabilityCatalog::VIEW_ANY,
            app('tenant'),
            $user,
        );

        return $scope === PermissionScopeCatalog::TENANT_ALL;
    }

    public function view(User $user, Asset $asset): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::ASSETS,
            CapabilityCatalog::VIEW,
            app('tenant'),
            $user,
        );

        return $this->recordScopeResolver()->allowsSharedScope($scope)
            && $scope === PermissionScopeCatalog::TENANT_ALL;
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
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::ASSETS,
            CapabilityCatalog::UPDATE,
            app('tenant'),
            $user,
        );

        return $this->recordScopeResolver()->allowsSharedScope($scope)
            && $scope === PermissionScopeCatalog::TENANT_ALL;
    }

    public function delete(User $user, Asset $asset): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::ASSETS,
            CapabilityCatalog::DELETE,
            app('tenant'),
            $user,
        );

        return $this->recordScopeResolver()->allowsSharedScope($scope)
            && $scope === PermissionScopeCatalog::TENANT_ALL;
    }
}
