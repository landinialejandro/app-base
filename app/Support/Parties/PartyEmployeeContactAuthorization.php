<?php

// FILE: app/Support/Parties/PartyEmployeeContactAuthorization.php | V1

namespace App\Support\Parties;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Catalogs\RoleCatalog;

class PartyEmployeeContactAuthorization
{
    public function allows(?User $user = null, ?Tenant $tenant = null): bool
    {
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);

        if (! $tenant || ! $user) {
            return false;
        }

        $membership = $user->memberships()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('roles')
            ->first();

        if (! $membership) {
            return false;
        }

        if ($membership->is_owner) {
            return true;
        }

        return $membership->roles->contains(
            fn ($role) => $role->slug === RoleCatalog::ADMIN
        );
    }
}