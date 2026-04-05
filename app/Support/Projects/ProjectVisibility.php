<?php

// FILE: app/Support/Projects/ProjectVisibility.php | V4

namespace App\Support\Projects;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use Illuminate\Database\Eloquent\Builder;

class ProjectVisibility
{
    public static function visibleQuery(?Tenant $tenant = null, ?User $user = null): Builder
    {
        $user = $user ?: auth()->user();
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);

        $query = Project::query();

        if (! $user || ! $tenant) {
            return $query->whereRaw('1 = 0');
        }

        $scope = app(RolePermissionResolver::class)->actionScope(
            ModuleCatalog::PROJECTS,
            CapabilityCatalog::VIEW,
            $tenant,
            $user
        );

        if ($scope === PermissionScopeCatalog::TENANT_ALL) {
            return $query;
        }

        if ($scope === PermissionScopeCatalog::LIMITED) {
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
