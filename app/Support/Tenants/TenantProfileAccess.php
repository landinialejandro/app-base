<?php

// FILE: app/Support/Tenants/TenantProfileAccess.php | V1

namespace App\Support\Tenants;

use App\Models\Membership;
use App\Models\Role;
use App\Models\User;
use App\Support\Catalogs\RoleCatalog;

class TenantProfileAccess
{
    public function actorMembershipFor(User $user): ?Membership
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return null;
        }

        return $user->memberships()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->first();
    }

    public function isActiveMembership(?Membership $membership): bool
    {
        return $membership?->status === 'active';
    }

    public function isOwner(?Membership $membership): bool
    {
        return $this->isActiveMembership($membership)
            && $membership->is_owner === true;
    }

    public function isAdmin(?Membership $membership): bool
    {
        if (! $this->isActiveMembership($membership)) {
            return false;
        }

        if ($membership->is_owner) {
            return false;
        }

        $membership->loadMissing('roles');

        return $membership->roles
            ->pluck('slug')
            ->contains(RoleCatalog::ADMIN);
    }

    public function canViewProfile(?Membership $actor): bool
    {
        return $this->isOwner($actor)
            || $this->isAdmin($actor);
    }

    public function canEditGeneral(?Membership $actor): bool
    {
        return $this->isOwner($actor);
    }

    public function canCreateTenantInvite(?Membership $actor): bool
    {
        return $this->isOwner($actor)
            || $this->isAdmin($actor);
    }

    public function canManagePermissionsForRole(?Membership $actor, string $roleSlug): bool
    {
        if (! RoleCatalog::isAssignable($roleSlug)) {
            return false;
        }

        if ($this->isOwner($actor)) {
            return true;
        }

        if (! $this->isAdmin($actor)) {
            return false;
        }

        return $roleSlug !== RoleCatalog::ADMIN;
    }

    public function canAssignRole(?Membership $actor, Membership $target, string $roleSlug): bool
    {
        if (! RoleCatalog::isAssignable($roleSlug)) {
            return false;
        }

        if (! $this->canManageTargetMembership($actor, $target)) {
            return false;
        }

        if ($this->isOwner($actor)) {
            return true;
        }

        if (! $this->isAdmin($actor)) {
            return false;
        }

        if ($roleSlug === RoleCatalog::ADMIN) {
            return false;
        }

        return ! $this->targetHasAdminRole($target);
    }

    public function canDetachRole(?Membership $actor, Membership $target, Role $role): bool
    {
        if (! $this->canManageTargetMembership($actor, $target)) {
            return false;
        }

        if ($this->isOwner($actor)) {
            return true;
        }

        if (! $this->isAdmin($actor)) {
            return false;
        }

        if ($role->slug === RoleCatalog::ADMIN) {
            return false;
        }

        return ! $this->targetHasAdminRole($target);
    }

    public function canManageMembershipStatus(?Membership $actor, Membership $target): bool
    {
        if (! $this->canManageTargetMembership($actor, $target)) {
            return false;
        }

        if ($this->isOwner($actor)) {
            return true;
        }

        if (! $this->isAdmin($actor)) {
            return false;
        }

        return ! $this->targetHasAdminRole($target);
    }

    protected function canManageTargetMembership(?Membership $actor, Membership $target): bool
    {
        $tenant = app('tenant');

        if (! $tenant || ! $actor) {
            return false;
        }

        if ($actor->tenant_id !== $tenant->id || $target->tenant_id !== $tenant->id) {
            return false;
        }

        if (! $this->isActiveMembership($actor)) {
            return false;
        }

        if ($target->is_owner) {
            return false;
        }

        if ($actor->id === $target->id) {
            return false;
        }

        if ($actor->user_id === $target->user_id) {
            return false;
        }

        return true;
    }

    protected function targetHasAdminRole(Membership $target): bool
    {
        $target->loadMissing('roles');

        return $target->roles
            ->pluck('slug')
            ->contains(RoleCatalog::ADMIN);
    }
}