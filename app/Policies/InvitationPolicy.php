<?php

// FILE: app/Policies/InvitationPolicy.php | V2

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use App\Support\Tenants\TenantProfileAccess;

class InvitationPolicy
{
    protected function access(): TenantProfileAccess
    {
        return app(TenantProfileAccess::class);
    }

    public function createTenantInvite(User $user): bool
    {
        return $this->access()->canCreateTenantInvite(
            $this->access()->actorMembershipFor($user)
        );
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        $tenant = app('tenant');

        if (! $tenant) {
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

        return $this->access()->canCreateTenantInvite(
            $this->access()->actorMembershipFor($user)
        );
    }
}