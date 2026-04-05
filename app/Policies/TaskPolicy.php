<?php

// FILE: app/Policies/TaskPolicy.php | V5

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\Auth\RecordScopeResolver;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;

class TaskPolicy
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
            ModuleCatalog::TASKS,
            CapabilityCatalog::VIEW_ANY,
            app('tenant'),
            $user
        );

        return $scope === PermissionScopeCatalog::TENANT_ALL;
    }

    public function view(User $user, Task $task): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::TASKS,
            CapabilityCatalog::VIEW,
            app('tenant'),
            $user
        );

        return $this->recordScopeResolver()->allowsSharedScope($scope);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::TASKS,
            CapabilityCatalog::CREATE,
            app('tenant'),
            $user
        );
    }

    public function update(User $user, Task $task): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::TASKS,
            CapabilityCatalog::UPDATE,
            app('tenant'),
            $user
        );

        if (! in_array($scope, [PermissionScopeCatalog::TENANT_ALL, PermissionScopeCatalog::OWN_ASSIGNED], true)) {
            return false;
        }

        return $this->recordScopeResolver()->allowsAssignedUserScope($scope, $task, $user);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::TASKS,
            CapabilityCatalog::DELETE,
            app('tenant'),
            $user
        );
    }
}
