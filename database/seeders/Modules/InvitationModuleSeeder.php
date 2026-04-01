<?php

// database/seeders/Modules/InvitationModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Invitation;
use Illuminate\Support\Str;

class InvitationModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants') || ! $this->hasDependency('users')) {
            throw new \RuntimeException('InvitationModuleSeeder requires tenants and users');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $invitations = [];

        // Tech invitations
        $invitations['tech'] = collect([
            $this->createInvitation($tenants['tech'], 'nuevo-admin@tech.local', $users['ownerTech']),
            $this->createInvitation($tenants['tech'], 'nuevo-operador@tech.local', $users['ownerTech']),
        ]);

        // Andina invitations
        $invitations['andina'] = collect([
            $this->createInvitation($tenants['andina'], 'nuevo-admin@andina.local', $users['ownerAndina']),
            $this->createInvitation($tenants['andina'], 'nuevo-obra@andina.local', $users['ownerAndina']),
        ]);

        $this->context['invitations'] = $invitations;
    }

    private function createInvitation($tenant, string $email, $invitedBy): Invitation
    {
        return Invitation::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => $email,
                'type' => 'member_invite',
            ],
            [
                'status' => 'pending',
                'token' => Str::random(64),
                'invited_by_user_id' => $invitedBy->id,
                'expires_at' => now()->addDays(7),
                'sent_at' => now(),
                'accepted_at' => null,
                'accepted_ip' => null,
                'user_agent' => 'DemoSeeder',
                'meta' => ['source' => 'demo-seeder'],
            ]
        );
    }
}
