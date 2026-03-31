<?php

// FILE: app/Policies/ProjectPolicy.php | V2

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Auth\TenantAccess;
use App\Support\Catalogs\ModuleCatalog;

class ProjectPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::PROJECTS, app('tenant'), $user);
    }

    public function view(User $user, Project $project): bool
    {
        $tenant = app('tenant');

        if (! $this->resolver()->canUseModule(ModuleCatalog::PROJECTS, $tenant, $user)) {
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
        return $this->resolver()->can(ModuleCatalog::PROJECTS, 'create', app('tenant'), $user);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->resolver()->can(ModuleCatalog::PROJECTS, 'update', app('tenant'), $user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->resolver()->can(ModuleCatalog::PROJECTS, 'delete', app('tenant'), $user);
    }
}
