<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;

class TaskPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::TASKS, app('tenant'), $user);
    }

    public function view(User $user, Task $task): bool
    {
        if (! $this->resolver()->canUseModule(ModuleCatalog::TASKS, app('tenant'), $user)) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(ModuleCatalog::TASKS, 'create', app('tenant'), $user);
    }

    public function update(User $user, Task $task): bool
    {
        $scope = $this->resolver()->actionScope(ModuleCatalog::TASKS, 'update', app('tenant'), $user);

        if ($scope === 'all') {
            return true;
        }

        if ($scope === 'own_assigned') {
            return (int) $task->assigned_user_id === (int) $user->id;
        }

        return false;
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->resolver()->can(ModuleCatalog::TASKS, 'delete', app('tenant'), $user);
    }
}
