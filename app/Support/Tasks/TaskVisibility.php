<?php

// FILE: app/Support/Tasks/TaskVisibility.php | V3

namespace App\Support\Tasks;

use App\Models\Task;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use Illuminate\Database\Eloquent\Builder;

class TaskVisibility
{
    public static function visibleQuery(?User $user = null): Builder
    {
        $user = $user ?: auth()->user();
        $tenant = app('tenant');

        $query = Task::query();

        if (! $user || ! $tenant) {
            return $query->whereRaw('1 = 0');
        }

        $resolver = app(RolePermissionResolver::class);

        $scope = $resolver->actionScope(
            ModuleCatalog::TASKS,
            CapabilityCatalog::VIEW_ANY,
            $tenant,
            $user
        );

        if ($scope === false || $scope === null || $scope === 'none') {
            $scope = $resolver->actionScope(
                ModuleCatalog::TASKS,
                CapabilityCatalog::VIEW,
                $tenant,
                $user
            );
        }

        if (in_array($scope, [true, 'tenant_all', 'all'], true)) {
            return $query;
        }

        if ($scope === 'own_assigned') {
            return $query->where('assigned_user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function mineQuery(?User $user = null): Builder
    {
        $user = $user ?: auth()->user();

        return static::visibleQuery($user)
            ->where('assigned_user_id', $user?->id);
    }
}
