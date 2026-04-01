<?php

// database/seeders/Modules/MembershipModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Membership;

class MembershipModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants') || ! $this->hasDependency('users')) {
            throw new \RuntimeException('MembershipModuleSeeder requires tenants and users');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');

        $memberships = [
            [$tenants['tech'], $users['ownerTech'], true],
            [$tenants['tech'], $users['shared'], false],
            [$tenants['tech'], $users['techUser'], false],
            [$tenants['andina'], $users['ownerAndina'], true],
            [$tenants['andina'], $users['shared'], false],
            [$tenants['andina'], $users['andinaUser'], false],
        ];

        foreach ($memberships as [$tenant, $user, $isOwner]) {
            Membership::firstOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $user->id],
                ['status' => 'active', 'is_owner' => $isOwner, 'joined_at' => now()]
            );
        }
    }
}
