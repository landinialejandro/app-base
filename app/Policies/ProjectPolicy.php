<?php

// FILE: app/Policies/ProjectPolicy.php | V3

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;

class ProjectPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::PROJECTS,
            'view_any',
            app('tenant'),
            $user
        );
    }

    public function view(User $user, Project $project): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::PROJECTS,
            'view',
            app('tenant'),
            $user
        );

        return $this->allowsProjectScope($scope, $project, $user);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::PROJECTS,
            'create',
            app('tenant'),
            $user
        );
    }

    public function update(User $user, Project $project): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::PROJECTS,
            'update',
            app('tenant'),
            $user
        );

        return $this->allowsProjectScope($scope, $project, $user);
    }

    public function delete(User $user, Project $project): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::PROJECTS,
            'delete',
            app('tenant'),
            $user
        );

        return $this->allowsProjectScope($scope, $project, $user);
    }

    protected function allowsProjectScope(mixed $scope, Project $project, User $user): bool
    {
        if (in_array($scope, [true, 'tenant_all', 'all'], true)) {
            return true;
        }

        if (in_array($scope, ['own_assigned', 'limited'], true)) {
            return $this->hasTaskParticipation($project, $user);
        }

        return false;
    }

    protected function hasTaskParticipation(Project $project, User $user): bool
    {
        return $project->tasks()
            ->where('assigned_user_id', $user->id)
            ->whereNull('deleted_at')
            ->exists();
    }
}
