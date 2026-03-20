<?php

// FILE: app/Support/Auth/RecordVisibility.php | V1

namespace App\Support\Auth;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RecordVisibility
{
    public static function visibleProjectsQuery(?User $user = null, ?string $tenantId = null): Builder
    {
        $user = $user ?: auth()->user();
        $tenantId = $tenantId ?: (app()->bound('tenant') ? app('tenant')->id : null);

        $query = Project::query();

        if (! $user || ! $tenantId) {
            return $query->whereRaw('1 = 0');
        }

        if (TenantAccess::isOwnerOrAdmin($tenantId, $user)) {
            return $query;
        }

        return $query->whereHas('tasks', function ($taskQuery) use ($user) {
            $taskQuery->where('assigned_user_id', $user->id);
        });
    }

    public static function visibleTasksQuery(?User $user = null, ?string $tenantId = null): Builder
    {
        $user = $user ?: auth()->user();
        $tenantId = $tenantId ?: (app()->bound('tenant') ? app('tenant')->id : null);

        $query = Task::query();

        if (! $user || ! $tenantId) {
            return $query->whereRaw('1 = 0');
        }

        if (TenantAccess::isOwnerOrAdmin($tenantId, $user)) {
            return $query;
        }

        return $query->where('assigned_user_id', $user->id);
    }
}
