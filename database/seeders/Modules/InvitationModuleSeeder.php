<?php

// FILE: database/seeders/Modules/InvitationModuleSeeder.php | V2

namespace Database\Seeders\Modules;

use App\Models\Invitation;
use Illuminate\Support\Collection;
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

        $invitations['tech'] = $this->createInvitations($tenants['tech'], [
            [
                'email' => 'nuevo-admin@tech.local',
                'invited_by_user_id' => $users['ownerTech']->id,
            ],
            [
                'email' => 'nuevo-operador@tech.local',
                'invited_by_user_id' => $users['ownerTech']->id,
            ],
        ]);

        $invitations['andina'] = $this->createInvitations($tenants['andina'], [
            [
                'email' => 'nuevo-admin@andina.local',
                'invited_by_user_id' => $users['ownerAndina']->id,
            ],
            [
                'email' => 'nuevo-obra@andina.local',
                'invited_by_user_id' => $users['ownerAndina']->id,
            ],
        ]);

        $this->context['invitations'] = $invitations;
    }

    private function createInvitations($tenant, array $definitions): Collection
    {
        $created = collect();

        foreach ($definitions as $definition) {
            $created->push(
                Invitation::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'email' => $definition['email'],
                        'type' => 'member_invite',
                    ],
                    [
                        'status' => 'pending',
                        'token' => Str::random(64),
                        'invited_by_user_id' => $definition['invited_by_user_id'],
                        'expires_at' => now()->addDays(7),
                        'sent_at' => now(),
                        'accepted_at' => null,
                        'accepted_ip' => null,
                        'user_agent' => 'DemoSeeder',
                        'meta' => [
                            'source' => 'demo-seeder',
                        ],
                    ]
                )
            );
        }

        return $created->values();
    }
}
