<?php

// FILE: app/Policies/TaskPolicy.php | V3

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;

class TaskPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::TASKS,
            CapabilityCatalog::VIEW_ANY,
            app('tenant'),
            $user
        );

        return in_array($scope, [true, 'tenant_all', 'all', 'own_assigned'], true);
    }

    public function view(User $user, Task $task): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::TASKS,
            CapabilityCatalog::VIEW,
            app('tenant'),
            $user
        );

        if (in_array($scope, [true, 'tenant_all', 'all'], true)) {
            return true;
        }

        if ($scope === 'own_assigned') {
            return (int) $task->assigned_user_id === (int) $user->id;
        }

        return false;
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

        if (in_array($scope, [true, 'all'], true)) {
            return true;
        }

        if ($scope === 'own_assigned') {
            return (int) $task->assigned_user_id === (int) $user->id;
        }

        return false;
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
