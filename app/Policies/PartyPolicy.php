<?php

// FILE: app/Policies/PartyPolicy.php | V4

namespace App\Policies;

use App\Models\Party;
use App\Models\User;
use App\Support\Auth\RecordScopeResolver;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;

class PartyPolicy
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
            ModuleCatalog::PARTIES,
            CapabilityCatalog::VIEW_ANY,
            app('tenant'),
            $user
        );

        return $scope === PermissionScopeCatalog::TENANT_ALL;
    }

    public function view(User $user, Party $party): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::PARTIES,
            CapabilityCatalog::VIEW,
            app('tenant'),
            $user
        );

        return $this->recordScopeResolver()->allowsSharedScope($scope)
            && $scope === PermissionScopeCatalog::TENANT_ALL;
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::PARTIES,
            CapabilityCatalog::CREATE,
            app('tenant'),
            $user
        );
    }

    public function update(User $user, Party $party): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::PARTIES,
            CapabilityCatalog::UPDATE,
            app('tenant'),
            $user
        );

        return $this->recordScopeResolver()->allowsSharedScope($scope)
            && $scope === PermissionScopeCatalog::TENANT_ALL;
    }

    public function delete(User $user, Party $party): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::PARTIES,
            CapabilityCatalog::DELETE,
            app('tenant'),
            $user
        );

        return $this->recordScopeResolver()->allowsSharedScope($scope)
            && $scope === PermissionScopeCatalog::TENANT_ALL;
    }
}
