<?php

// FILE: app/Support/Tasks/TaskVisibility.php | V2

namespace App\Support\Tasks;

use App\Models\Task;
use App\Models\User;
use App\Support\Auth\TenantAccess;
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

        if (TenantAccess::isOwnerOrAdmin($tenant->id, $user)) {
            return $query;
        }

        return $query->where(function (Builder $visibleQuery) use ($user) {
            $visibleQuery->where('assigned_user_id', $user->id)
                ->orWhere(function (Builder $projectScopeQuery) use ($user) {
                    $projectScopeQuery->whereNotNull('project_id')
                        ->whereExists(function ($subquery) use ($user) {
                            $subquery->selectRaw('1')
                                ->from('tasks as user_project_tasks')
                                ->whereColumn('user_project_tasks.project_id', 'tasks.project_id')
                                ->whereNull('user_project_tasks.deleted_at')
                                ->where('user_project_tasks.assigned_user_id', $user->id);
                        });
                });
        });
    }

    public static function mineQuery(?User $user = null): Builder
    {
        $user = $user ?: auth()->user();

        return static::visibleQuery($user)
            ->where('assigned_user_id', $user?->id);
    }
}
