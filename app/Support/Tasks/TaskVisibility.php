<?php

// FILE: app/Support/Tasks/TaskVisibility.php | V5

namespace App\Support\Tasks;

use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TaskVisibility
{
    public static function visibleQuery(
        ?Builder $query = null,
        ?Tenant $tenant = null,
        ?User $user = null
    ): Builder {
        $user = $user ?: auth()->user();
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);
        $query = $query ?: Task::query();

        if (! $user || ! $tenant) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($user) {
            $q->where('assigned_user_id', $user->id)
                ->orWhereIn('project_id', function ($sub) use ($user) {
                    $sub->select('project_id')
                        ->from('tasks')
                        ->whereNull('deleted_at')
                        ->where('assigned_user_id', $user->id)
                        ->whereNotNull('project_id');
                });
        });
    }
}
