<?php

// FILE: app/Support/Auth/RecordVisibility.php | V2

namespace App\Support\Auth;

use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use Illuminate\Database\Eloquent\Builder;

class RecordVisibility
{
    public static function visibleProjectsQuery(
        ?User $user = null,
        ?string $tenantId = null,
        ?Tenant $tenant = null
    ): Builder {
        $user = $user ?: auth()->user();
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);

        if ($tenant === null && $tenantId !== null) {
            $tenant = Tenant::query()->find($tenantId);
        }

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
            return $query->whereHas('tasks', function ($taskQuery) use ($user) {
                $taskQuery->where('assigned_user_id', $user->id)
                    ->whereNull('deleted_at');
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function visibleTasksQuery(
        ?User $user = null,
        ?string $tenantId = null,
        ?Tenant $tenant = null
    ): Builder {
        $user = $user ?: auth()->user();
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);

        if ($tenant === null && $tenantId !== null) {
            $tenant = Tenant::query()->find($tenantId);
        }

        $query = Task::query();

        if (! $user || ! $tenant) {
            return $query->whereRaw('1 = 0');
        }

        $viewAnyScope = app(RolePermissionResolver::class)->actionScope(
            ModuleCatalog::TASKS,
            CapabilityCatalog::VIEW_ANY,
            $tenant,
            $user
        );

        if ($viewAnyScope === PermissionScopeCatalog::TENANT_ALL) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }
}
