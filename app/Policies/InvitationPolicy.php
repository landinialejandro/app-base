<?php

// FILE: app/Policies/InvitationPolicy.php | V1

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    protected function currentUserIsOwner(User $user): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        $membership = $user->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();

        return (bool) $membership?->is_owner;
    }

    public function createTenantInvite(User $user): bool
    {
        return $this->currentUserIsOwner($user);
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        if (! $this->currentUserIsOwner($user)) {
            return false;
        }

        if ($invitation->tenant_id !== $tenant->id) {
            return false;
        }

        if ($invitation->type !== 'member_invite') {
            return false;
        }

        if ($invitation->accepted_at) {
            return false;
        }

        return true;
    }
}
