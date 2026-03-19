<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\Auth\RoleModuleAccess;
use App\Support\Auth\TenantAccess;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse('tasks', $tenant, $user);
    }

    public function view(User $user, Task $task): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse('tasks', $tenant, $user)) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse('tasks', $tenant, $user);
    }

    public function update(User $user, Task $task): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse('tasks', $tenant, $user)) {
            return false;
        }

        if (TenantAccess::isOwnerOrAdmin($tenant->id, $user)) {
            return true;
        }

        return (int) $task->assigned_user_id === (int) $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse('tasks', $tenant, $user)) {
            return false;
        }

        return TenantAccess::isOwnerOrAdmin($tenant->id, $user);
    }
}
