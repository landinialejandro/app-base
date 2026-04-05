<?php

// FILE: app/Support/Auth/Security.php | V1

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Security
{
    public function __construct(
        protected RolePermissionResolver $permissions,
        protected RecordScopeResolver $recordScopes,
    ) {}

    public function authorize(User $user, string $ability, mixed $subject = null, array $context = []): void
    {
        if (! $this->allows($user, $ability, $subject, $context)) {
            throw new AuthorizationException('Esta acción no está autorizada.');
        }
    }

    public function allows(User $user, string $ability, mixed $subject = null, array $context = []): bool
    {
        [$module, $capability] = $this->parseAbility($ability);

        $tenant = app('tenant');
        $scope = $this->permissions->actionScope($module, $capability, $tenant, $user);
        $constraints = $this->permissions->constraints($module, $capability, $tenant, $user);

        if ($subject instanceof Model) {
            return $this->recordScopes->allows(
                module: $module,
                capability: $capability,
                scope: $scope,
                constraints: $constraints,
                record: $subject,
                user: $user,
                context: $context,
            );
        }

        if (is_string($subject) && class_exists($subject)) {
            return $this->recordScopes->allowsCreateContext(
                module: $module,
                capability: $capability,
                scope: $scope,
                constraints: $constraints,
                context: $context,
            );
        }

        if ($this->recordScopes->deniesByConfiguration($module, $capability, $scope, $constraints)) {
            return false;
        }

        return $scope !== false;
    }

    public function scope(User $user, string $ability, Builder $query, array $context = []): Builder
    {
        [$module, $capability] = $this->parseAbility($ability);

        $tenant = app('tenant');
        $scope = $this->permissions->actionScope($module, $capability, $tenant, $user);
        $constraints = $this->permissions->constraints($module, $capability, $tenant, $user);

        return $this->recordScopes->applyToQuery(
            module: $module,
            capability: $capability,
            query: $query,
            user: $user,
            scope: $scope,
            constraints: $constraints,
            context: $context,
        );
    }

    public function inspect(User $user, string $ability, mixed $subject = null, array $context = []): array
    {
        [$module, $capability] = $this->parseAbility($ability);

        $tenant = app('tenant');
        $scope = $this->permissions->actionScope($module, $capability, $tenant, $user);
        $constraints = $this->permissions->constraints($module, $capability, $tenant, $user);

        return [
            'module' => $module,
            'capability' => $capability,
            'scope' => $scope,
            'constraints' => $constraints,
            'allowed' => $this->allows($user, $ability, $subject, $context),
            'subject_type' => $subject instanceof Model ? $subject::class : (is_string($subject) ? $subject : null),
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'context' => $context,
        ];
    }

    protected function parseAbility(string $ability): array
    {
        $parts = explode('.', $ability, 2);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Ability inválida: {$ability}");
        }

        [$module, $action] = $parts;

        $capability = match ($action) {
            'viewAny' => 'view_any',
            'view' => 'view',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
            default => throw new InvalidArgumentException("Action inválida en ability: {$ability}"),
        };

        return [$module, $capability];
    }
}
