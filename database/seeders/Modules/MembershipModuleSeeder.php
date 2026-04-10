<?php

// FILE: database/seeders/Modules/MembershipModuleSeeder.php | V3

namespace Database\Seeders\Modules;

use App\Models\Membership;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Support\Facades\DB;

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
            [
                'tenant' => $tenants['tech'],
                'user' => $users['ownerTech'],
                'is_owner' => true,
                'status' => 'active',
                'profile_slug' => null,
                'roles' => [],
            ],
            [
                'tenant' => $tenants['tech'],
                'user' => $users['shared'],
                'is_owner' => false,
                'status' => 'active',
                'profile_slug' => null,
                'roles' => [RoleCatalog::OPERATOR],
            ],
            [
                'tenant' => $tenants['tech'],
                'user' => $users['techUser'],
                'is_owner' => false,
                'status' => 'active',
                'profile_slug' => null,
                'roles' => [RoleCatalog::SALES],
            ],
            [
                'tenant' => $tenants['andina'],
                'user' => $users['ownerAndina'],
                'is_owner' => true,
                'status' => 'active',
                'profile_slug' => null,
                'roles' => [],
            ],
            [
                'tenant' => $tenants['andina'],
                'user' => $users['shared'],
                'is_owner' => false,
                'status' => 'active',
                'profile_slug' => null,
                'roles' => [RoleCatalog::ADMINISTRATOR],
            ],
            [
                'tenant' => $tenants['andina'],
                'user' => $users['andinaUser'],
                'is_owner' => false,
                'status' => 'active',
                'profile_slug' => null,
                'roles' => [RoleCatalog::OPERATOR],
            ],
        ];

        $createdMemberships = [];

        foreach ($memberships as $definition) {
            if (! $definition['is_owner'] && empty($definition['roles'])) {
                throw new \RuntimeException('Non-owner membership requires at least one role.');
            }

            $membership = Membership::updateOrCreate(
                [
                    'tenant_id' => $definition['tenant']->id,
                    'user_id' => $definition['user']->id,
                ],
                [
                    'status' => $definition['status'],
                    'is_owner' => $definition['is_owner'],
                    'profile_slug' => $definition['profile_slug'],
                    'joined_at' => now(),
                    'blocked_at' => null,
                    'blocked_reason' => null,
                ]
            );

            $this->syncMembershipRoles($membership->id, $definition['tenant']->id, $definition['roles']);

            $createdMemberships[] = $membership;
        }

        $this->context['memberships'] = collect($createdMemberships);
    }

    private function syncMembershipRoles(int $membershipId, string $tenantId, array $roleSlugs): void
    {
        DB::table('membership_role')
            ->where('membership_id', $membershipId)
            ->delete();

        if (empty($roleSlugs)) {
            return;
        }

        $roleIds = DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->whereIn('slug', $roleSlugs)
            ->pluck('id', 'slug');

        foreach ($roleSlugs as $roleSlug) {
            $roleId = $roleIds[$roleSlug] ?? null;

            if (! $roleId) {
                throw new \RuntimeException("Role [$roleSlug] not found for tenant [$tenantId].");
            }

            DB::table('membership_role')->updateOrInsert(
                [
                    'membership_id' => $membershipId,
                    'role_id' => $roleId,
                ],
                [
                    'branch_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
