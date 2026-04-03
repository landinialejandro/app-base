<?php

// FILE: app/Policies/ProjectPolicy.php | V4

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\Auth\RecordScopeResolver;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;

class ProjectPolicy
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
        return $this->resolver()->can(
            ModuleCatalog::PROJECTS,
            CapabilityCatalog::VIEW_ANY,
            app('tenant'),
            $user
        );
    }

    public function view(User $user, Project $project): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::PROJECTS,
            CapabilityCatalog::VIEW,
            app('tenant'),
            $user
        );

        if (! in_array($scope, [PermissionScopeCatalog::TENANT_ALL, PermissionScopeCatalog::LIMITED], true)) {
            return false;
        }

        return $this->recordScopeResolver()->allowsProjectScope($scope, $project, $user);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::PROJECTS,
            CapabilityCatalog::CREATE,
            app('tenant'),
            $user
        );
    }

    public function update(User $user, Project $project): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::PROJECTS,
            CapabilityCatalog::UPDATE,
            app('tenant'),
            $user
        );

        if (! in_array($scope, [PermissionScopeCatalog::TENANT_ALL, PermissionScopeCatalog::LIMITED], true)) {
            return false;
        }

        return $this->recordScopeResolver()->allowsProjectScope($scope, $project, $user);
    }

    public function delete(User $user, Project $project): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::PROJECTS,
            CapabilityCatalog::DELETE,
            app('tenant'),
            $user
        );

        if (! in_array($scope, [PermissionScopeCatalog::TENANT_ALL, PermissionScopeCatalog::LIMITED], true)) {
            return false;
        }

        return $this->recordScopeResolver()->allowsProjectScope($scope, $project, $user);
    }
}
