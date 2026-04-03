<?php

// FILE: app/Support/Projects/ProjectVisibility.php | V2

namespace App\Support\Projects;

use App\Models\Project;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;
use Illuminate\Database\Eloquent\Builder;

class ProjectVisibility
{
    public static function visibleQuery(?User $user = null): Builder
    {
        $user = $user ?: auth()->user();
        $tenant = app('tenant');

        $query = Project::query();

        if (! $user || ! $tenant) {
            return $query->whereRaw('1 = 0');
        }

        $resolver = app(RolePermissionResolver::class);

        $scope = $resolver->actionScope(
            ModuleCatalog::PROJECTS,
            'view_any',
            $tenant,
            $user
        );

        if (in_array($scope, [true, 'tenant_all', 'all'], true)) {
            return $query;
        }

        if (in_array($scope, ['own_assigned', 'limited'], true)) {
            return $query->whereExists(function ($subquery) use ($user) {
                $subquery->selectRaw('1')
                    ->from('tasks')
                    ->whereColumn('tasks.project_id', 'projects.id')
                    ->whereNull('tasks.deleted_at')
                    ->where('tasks.assigned_user_id', $user->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
