<?php

// FILE: app/Support/Tenants/TenantUserDirectory.php | V1

namespace App\Support\Tenants;

use App\Models\Membership;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TenantUserDirectory
{
    public function activeMembershipsQuery(Tenant $tenant): Builder
    {
        return Membership::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('user')
            ->orderByDesc('is_owner')
            ->orderBy('id');
    }

    public function activeMemberships(Tenant $tenant): Collection
    {
        return $this->activeMembershipsQuery($tenant)->get();
    }

    public function activeUsersQuery(Tenant $tenant): Builder
    {
        return User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->orderBy('id');
    }

    public function activeUsers(Tenant $tenant): Collection
    {
        return $this->activeUsersQuery($tenant)->get();
    }

    public function userBelongsToTenant(Tenant $tenant, int $userId): bool
    {
        return Membership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    public function defaultAssignedUserId(Tenant $tenant, User $authUser): ?int
    {
        if ($this->userBelongsToTenant($tenant, (int) $authUser->id)) {
            return (int) $authUser->id;
        }

        return $this->activeUsersQuery($tenant)->value('id');
    }

    public function selectOptions(Tenant $tenant): Collection
    {
        return $this->activeUsers($tenant)
            ->map(function (User $user) {
                return [
                    'value' => (string) $user->id,
                    'label' => $user->name,
                ];
            })
            ->values();
    }
}
