<?php

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

        return $query;
    }

    public static function mineQuery(?User $user = null): Builder
    {
        $user = $user ?: auth()->user();

        return static::visibleQuery($user)
            ->where('assigned_user_id', $user?->id);
    }
}
