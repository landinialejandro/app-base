<?php

// database/seeders/Modules/RoleModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants')) {
            throw new \RuntimeException('RoleModuleSeeder requires tenants');
        }

        $tenants = $this->getDependency('tenants');
        $roles = [];

        // Tech roles
        $techRoles = $this->createRoles($tenants['tech']->id, [
            ['name' => 'Owner', 'slug' => 'owner', 'description' => 'Propietario del tenant.'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrador general.'],
            ['name' => 'Operador', 'slug' => 'operator', 'description' => 'Usuario operativo.'],
            ['name' => 'Comercial', 'slug' => 'sales', 'description' => 'Usuario comercial.'],
        ]);

        // Andina roles
        $andinaRoles = $this->createRoles($tenants['andina']->id, [
            ['name' => 'Owner', 'slug' => 'owner', 'description' => 'Propietario del tenant.'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrador general.'],
            ['name' => 'Operador', 'slug' => 'operator', 'description' => 'Usuario operativo.'],
            ['name' => 'Obra', 'slug' => 'site', 'description' => 'Usuario de obra.'],
        ]);

        $roles['tech'] = $techRoles;
        $roles['andina'] = $andinaRoles;

        // Assign permissions to roles
        $this->assignPermissionsToRoles($tenants['tech']->id, $techRoles);
        $this->assignPermissionsToRoles($tenants['andina']->id, $andinaRoles);

        $this->context['roles'] = $roles;
    }

    private function createRoles(string $tenantId, array $rolesData): array
    {
        $roles = [];

        foreach ($rolesData as $data) {
            $roles[$data['slug']] = Role::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'slug' => $data['slug'],
                ],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                ]
            );
        }

        return $roles;
    }

    private function assignPermissionsToRoles(string $tenantId, array $roles): void
    {
        $permissions = Permission::all()->keyBy('slug');

        // Permission matrix by role
        $matrix = [
            'owner' => ['projects', 'tasks', 'parties', 'products', 'orders', 'documents', 'assets', 'appointments'],
            'admin' => ['projects', 'tasks', 'parties', 'products', 'orders', 'documents', 'assets', 'appointments'],
            'sales' => ['parties', 'products', 'orders', 'documents'],
            'operator' => ['tasks', 'assets', 'appointments'],
            'site' => ['tasks', 'assets', 'appointments'],
        ];

        foreach ($matrix as $roleSlug => $modules) {
            if (! isset($roles[$roleSlug])) {
                continue;
            }

            $role = $roles[$roleSlug];

            foreach ($modules as $module) {
                // Assign view permission
                $viewPerm = $permissions[$module.'.view'] ?? null;
                if ($viewPerm) {
                    $this->attachPermissionToRole($role->id, $viewPerm->id);
                }

                // Assign create permission for some roles
                if (in_array($roleSlug, ['owner', 'admin', 'sales'])) {
                    $createPerm = $permissions[$module.'.create'] ?? null;
                    if ($createPerm) {
                        $this->attachPermissionToRole($role->id, $createPerm->id);
                    }
                }

                // Assign update permission for owner and admin
                if (in_array($roleSlug, ['owner', 'admin'])) {
                    $updatePerm = $permissions[$module.'.update'] ?? null;
                    if ($updatePerm) {
                        $this->attachPermissionToRole($role->id, $updatePerm->id);
                    }
                }

                // Assign delete permission only for owner
                if ($roleSlug === 'owner') {
                    $deletePerm = $permissions[$module.'.delete'] ?? null;
                    if ($deletePerm) {
                        $this->attachPermissionToRole($role->id, $deletePerm->id);
                    }
                }
            }
        }
    }

    private function attachPermissionToRole(int $roleId, int $permissionId): void
    {
        DB::table('role_permission')->updateOrInsert(
            [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
