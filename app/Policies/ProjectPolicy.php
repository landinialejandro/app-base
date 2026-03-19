<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\Auth\RoleModuleAccess;
use App\Support\Auth\TenantAccess;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse('projects', $tenant, $user);
    }

    public function view(User $user, Project $project): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse('projects', $tenant, $user)) {
            return false;
        }

        if (TenantAccess::isOwnerOrAdmin($tenant->id, $user)) {
            return true;
        }

        return $project->tasks()
            ->where('assigned_user_id', $user->id)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function create(User $user): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse('projects', $tenant, $user)) {
            return false;
        }

        return TenantAccess::isOwnerOrAdmin($tenant->id, $user);
    }

    public function update(User $user, Project $project): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse('projects', $tenant, $user)) {
            return false;
        }

        return TenantAccess::isOwnerOrAdmin($tenant->id, $user);
    }

    public function delete(User $user, Project $project): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse('projects', $tenant, $user)) {
            return false;
        }

        return TenantAccess::isOwnerOrAdmin($tenant->id, $user);
    }
}
