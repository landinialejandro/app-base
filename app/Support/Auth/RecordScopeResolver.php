<?php

// FILE: app/Support/Auth/RecordScopeResolver.php | V3

namespace App\Support\Auth;

use App\Models\Project;
use App\Models\User;
use App\Support\Catalogs\PermissionScopeCatalog;
use Illuminate\Database\Eloquent\Model;

class RecordScopeResolver
{
    public function allowsSharedScope(mixed $scope): bool
    {
        return in_array($scope, [
            PermissionScopeCatalog::TENANT_ALL,
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

        if ($scope !== PermissionScopeCatalog::OWN_ASSIGNED) {
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

        if ($scope !== PermissionScopeCatalog::LIMITED) {
            return false;
        }

        return $project->tasks()
            ->where('assigned_user_id', $user->id)
            ->whereNull('deleted_at')
            ->exists();
    }
}
