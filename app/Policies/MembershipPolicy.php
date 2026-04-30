<?php

// FILE: app/Policies/MembershipPolicy.php | V1

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;

class MembershipPolicy
{
    protected function ownerMembershipForCurrentTenant(User $user): ?Membership
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return null;
        }

        return $user->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();
    }

    protected function currentUserIsOwner(User $user): bool
    {
        return (bool) $this->ownerMembershipForCurrentTenant($user)?->is_owner;
    }

    public function block(User $user, Membership $membership): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        if (! $this->currentUserIsOwner($user)) {
            return false;
        }

        if ($membership->tenant_id !== $tenant->id) {
            return false;
        }

        if ($membership->is_owner) {
            return false;
        }

        if ($membership->user_id === $user->id) {
            return false;
        }

        return true;
    }

    public function unblock(User $user, Membership $membership): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        if (! $this->currentUserIsOwner($user)) {
            return false;
        }

        if ($membership->tenant_id !== $tenant->id) {
            return false;
        }

        if ($membership->is_owner) {
            return false;
        }

        if ($membership->user_id === $user->id) {
            return false;
        }

        return true;
    }

    public function attachRole(User $user, Membership $membership): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        if (! $this->currentUserIsOwner($user)) {
            return false;
        }

        if ($membership->tenant_id !== $tenant->id) {
            return false;
        }

        if ($membership->is_owner) {
            return false;
        }

        return true;
    }

    public function detachRole(User $user, Membership $membership): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        if (! $this->currentUserIsOwner($user)) {
            return false;
        }

        if ($membership->tenant_id !== $tenant->id) {
            return false;
        }

        if ($membership->is_owner) {
            return false;
        }

        return true;
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
    
        return $this->currentUserIsOwner($user);
    }
}
