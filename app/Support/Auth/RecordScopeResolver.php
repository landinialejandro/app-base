<?php

// FILE: app/Support/Auth/RecordScopeResolver.php | V6

namespace App\Support\Auth;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Projects\ProjectVisibility;
use App\Support\Tasks\TaskVisibility;
use Illuminate\Database\Eloquent\Builder;
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

    public function deniesByConfiguration(
        string $module,
        string $capability,
        mixed $scope,
        array $constraints = []
    ): bool {
        if ($scope === false || $scope === null || $scope === '') {
            return true;
        }

        if ($scope === PermissionScopeCatalog::LIMITED && ! $this->supportsLimitedModule($module)) {
            return true;
        }

        if ($this->requiresAllowedKinds($module, $capability)) {
            $allowedKinds = $this->extractAllowedKinds($constraints);

            if (empty($allowedKinds)) {
                return true;
            }
        }

        return false;
    }

    public function allows(
        string $module,
        string $capability,
        mixed $scope,
        array $constraints,
        Model $record,
        User $user,
        array $context = []
    ): bool {
        if ($this->deniesByConfiguration($module, $capability, $scope, $constraints)) {
            return false;
        }

        if (! $this->allowsByScope($module, $scope, $record, $user, $context)) {
            return false;
        }

        return $this->allowsByConstraints($constraints, $record, $context);
    }

    public function allowsCreateContext(
        string $module,
        string $capability,
        mixed $scope,
        array $constraints,
        array $context = []
    ): bool {
        if ($this->deniesByConfiguration($module, $capability, $scope, $constraints)) {
            return false;
        }

        if ($capability !== CapabilityCatalog::CREATE) {
            return $scope !== false;
        }

        if ($this->requiresAllowedKinds($module, $capability)) {
            $allowedKinds = $this->extractAllowedKinds($constraints);
            $kind = $context['kind'] ?? null;

            if (! is_string($kind) || trim($kind) === '') {
                return false;
            }

            return in_array($kind, $allowedKinds, true);
        }

        return $scope !== false;
    }

    public function applyToQuery(
        string $module,
        string $capability,
        Builder $query,
        User $user,
        mixed $scope,
        array $constraints = [],
        array $context = []
    ): Builder {
        if ($this->deniesByConfiguration($module, $capability, $scope, $constraints)) {
            return $query->whereRaw('1 = 0');
        }

        $query = $this->applyScopeToQuery(
            module: $module,
            query: $query,
            user: $user,
            scope: $scope,
            context: $context,
        );

        return $this->applyConstraintsToQuery($query, $constraints);
    }

    protected function allowsByScope(
        string $module,
        mixed $scope,
        Model $record,
        User $user,
        array $context = []
    ): bool {
        if ($scope === PermissionScopeCatalog::TENANT_ALL) {
            return true;
        }

        if ($scope === PermissionScopeCatalog::OWN_ASSIGNED) {
            return $this->allowsAssignedUserScope($scope, $record, $user);
        }

        if ($scope === PermissionScopeCatalog::LIMITED) {
            return $this->allowsLimitedRecord($module, $record, $user, $context);
        }

        return false;
    }

    protected function applyScopeToQuery(
        string $module,
        Builder $query,
        User $user,
        mixed $scope,
        array $context = []
    ): Builder {
        if ($scope === PermissionScopeCatalog::TENANT_ALL) {
            return $query;
        }

        if ($scope === PermissionScopeCatalog::OWN_ASSIGNED) {
            return $query->where(
                $query->getModel()->qualifyColumn('assigned_user_id'),
                $user->id
            );
        }

        if ($scope === PermissionScopeCatalog::LIMITED) {
            return $this->applyLimitedScope($module, $query, $user, $context);
        }

        return $query->whereRaw('1 = 0');
    }

    protected function allowsByConstraints(array $constraints, Model $record, array $context = []): bool
    {
        $allowedKinds = $this->extractAllowedKinds($constraints);

        if (! empty($allowedKinds)) {
            $kind = $record->getAttribute('kind');

            if (! is_string($kind) || ! in_array($kind, $allowedKinds, true)) {
                return false;
            }
        }

        return true;
    }

    protected function applyConstraintsToQuery(Builder $query, array $constraints): Builder
    {
        $allowedKinds = $this->extractAllowedKinds($constraints);

        if (! empty($allowedKinds) && $this->modelHasColumn($query->getModel(), 'kind')) {
            $query->whereIn(
                $query->getModel()->qualifyColumn('kind'),
                $allowedKinds
            );
        }

        return $query;
    }

    protected function allowsLimitedRecord(
        string $module,
        Model $record,
        User $user,
        array $context = []
    ): bool {
        if ($module === ModuleCatalog::PROJECTS && $record instanceof Project) {
            return $this->allowsProjectScope(PermissionScopeCatalog::LIMITED, $record, $user);
        }

        if ($module === ModuleCatalog::TASKS && $record instanceof Task) {
            return TaskVisibility::visibleQuery(
                Task::query(),
                app()->bound('tenant') ? app('tenant') : null,
                $user
            )->whereKey($record->getKey())->exists();
        }

        return false;
    }

    protected function applyLimitedScope(
        string $module,
        Builder $query,
        User $user,
        array $context = []
    ): Builder {
        if ($module === ModuleCatalog::PROJECTS) {
            return ProjectVisibility::visibleQuery(
                $query,
                app()->bound('tenant') ? app('tenant') : null,
                $user
            );
        }

        if ($module === ModuleCatalog::TASKS) {
            return TaskVisibility::visibleQuery(
                $query,
                app()->bound('tenant') ? app('tenant') : null,
                $user
            );
        }

        return $query->whereRaw('1 = 0');
    }

    protected function requiresAllowedKinds(string $module, string $capability): bool
    {
        if (! in_array($capability, [
            CapabilityCatalog::VIEW_ANY,
            CapabilityCatalog::VIEW,
            CapabilityCatalog::CREATE,
            CapabilityCatalog::UPDATE,
            CapabilityCatalog::DELETE,
        ], true)) {
            return false;
        }

        return in_array($module, [
            ModuleCatalog::ORDERS,
            ModuleCatalog::DOCUMENTS,
        ], true);
    }

    public function extractAllowedKinds(array $constraints): array
    {
        $allowedKinds = $constraints['allowed_kinds'] ?? null;

        if (! is_array($allowedKinds)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $allowedKinds,
            fn ($value) => is_string($value) && trim($value) !== ''
        )));
    }

    public function supportsLimitedModule(string $module): bool
    {
        return in_array($module, [
            ModuleCatalog::PROJECTS,
            ModuleCatalog::TASKS,
        ], true);
    }

    protected function modelHasColumn(Model $model, string $column): bool
    {
        return array_key_exists($column, $model->getAttributes())
            || in_array($column, $model->getFillable(), true);
    }
}
