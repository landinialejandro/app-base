<?php

// FILE: app/Support/Auth/RecordScopeResolver.php | V2

namespace App\Support\Auth;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordScopeResolver
{
    public function allowsSharedScope(mixed $scope): bool
    {
        return in_array($scope, [
            true,
            'tenant_all',
            'all',
        ], true);
    }

    public function allowsAssignedUserScope(
        mixed $scope,
        Model $model,
        User $user,
        string $column = 'assigned_user_id'
    ): bool {
        if ($this->allowsSharedScope($scope)) {
            return true;
        }

        if ($scope !== 'own_assigned') {
            return false;
        }

        $assignedUserId = $model->getAttribute($column);

        if ($assignedUserId === null) {
            return false;
        }

        return (int) $assignedUserId === (int) $user->id;
    }

    public function allowsProjectScope(mixed $scope, Project $project, User $user): bool
    {
        if ($this->allowsSharedScope($scope)) {
            return true;
        }

        if ($scope !== 'limited') {
            return false;
        }

        return $project->tasks()
            ->where('assigned_user_id', $user->id)
            ->whereNull('deleted_at')
            ->exists();
    }
}
