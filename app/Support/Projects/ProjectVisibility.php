<?php

namespace App\Support\Projects;

use App\Models\Project;
use App\Models\User;
use App\Support\Auth\TenantAccess;
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

        if (TenantAccess::isOwnerOrAdmin($tenant->id, $user)) {
            return $query;
        }

        return $query->whereExists(function ($subquery) use ($user) {
            $subquery->selectRaw('1')
                ->from('tasks')
                ->whereColumn('tasks.project_id', 'projects.id')
                ->whereNull('tasks.deleted_at')
                ->where('tasks.assigned_user_id', $user->id);
        });
    }
}
