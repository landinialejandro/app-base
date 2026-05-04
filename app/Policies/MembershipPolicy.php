<?php

// FILE: app/Policies/MembershipPolicy.php | V2

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;
use App\Support\Tenants\TenantProfileAccess;

class MembershipPolicy
{
    protected function access(): TenantProfileAccess
    {
        return app(TenantProfileAccess::class);
    }

    protected function actorMembershipForCurrentTenant(User $user): ?Membership
    {
        return $this->access()->actorMembershipFor($user);
    }

    public function block(User $user, Membership $membership): bool
    {
        return $this->access()->canManageMembershipStatus(
            $this->actorMembershipForCurrentTenant($user),
            $membership
        );
    }

    public function unblock(User $user, Membership $membership): bool
    {
        return $this->access()->canManageMembershipStatus(
            $this->actorMembershipForCurrentTenant($user),
            $membership
        );
    }

    public function attachRole(User $user, Membership $membership): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        if ($membership->tenant_id !== $tenant->id) {
            return false;
        }

        if ($membership->is_owner) {
            return false;
        }

        $actor = $this->actorMembershipForCurrentTenant($user);

        return $this->access()->isOwner($actor)
            || $this->access()->isAdmin($actor);
    }

    public function detachRole(User $user, Membership $membership): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        if ($membership->tenant_id !== $tenant->id) {
            return false;
        }

        if ($membership->is_owner) {
            return false;
        }

        $actor = $this->actorMembershipForCurrentTenant($user);

        return $this->access()->isOwner($actor)
            || $this->access()->isAdmin($actor);
    }

    public function resolveParty(User $user, Membership $membership): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        if ($membership->tenant_id !== $tenant->id) {
            return false;
        }

        if ($membership->user_id === $user->id && $membership->status === 'active') {
            return true;
        }

        return $this->access()->isOwner(
            $this->actorMembershipForCurrentTenant($user)
        );
    }
}