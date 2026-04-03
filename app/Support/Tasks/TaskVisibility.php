<?php

// FILE: app/Support/Tasks/TaskVisibility.php | V3

namespace App\Support\Tasks;

use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use Illuminate\Database\Eloquent\Builder;

class TaskVisibility
{
    public static function visibleQuery(?Tenant $tenant = null, ?User $user = null): Builder
    {
        $user = $user ?: auth()->user();
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);

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
