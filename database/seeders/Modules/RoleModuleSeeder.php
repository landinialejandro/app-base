<?php

// FILE: database/seeders/Modules/RoleModuleSeeder.php | V2

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
            ['name' => 'Propietario', 'slug' => 'owner', 'description' => 'Propietario del tenant.'],
            ['name' => 'Administrador', 'slug' => 'admin', 'description' => 'Administrador general.'],
            ['name' => 'Comercial', 'slug' => 'sales', 'description' => 'Usuario comercial.'],
            ['name' => 'Operador', 'slug' => 'operator', 'description' => 'Usuario operativo.'],
            ['name' => 'Administrativo', 'slug' => 'administrator', 'description' => 'Usuario administrativo.'],
        ]);

        // Andina roles
        $andinaRoles = $this->createRoles($tenants['andina']->id, [
            ['name' => 'Propietario', 'slug' => 'owner', 'description' => 'Propietario del tenant.'],
            ['name' => 'Administrador', 'slug' => 'admin', 'description' => 'Administrador general.'],
            ['name' => 'Comercial', 'slug' => 'sales', 'description' => 'Usuario comercial.'],
            ['name' => 'Operador', 'slug' => 'operator', 'description' => 'Usuario operativo.'],
            ['name' => 'Administrativo', 'slug' => 'administrator', 'description' => 'Usuario administrativo.'],
        ]);

        $roles['tech'] = $techRoles;
        $roles['andina'] = $andinaRoles;

        $this->assignPermissionsToRoles($techRoles);
        $this->assignPermissionsToRoles($andinaRoles);

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

    private function assignPermissionsToRoles(array $roles): void
    {
        $permissions = Permission::all()->keyBy('slug');

        $matrix = [
            'owner' => ['projects', 'tasks', 'parties', 'products', 'orders', 'documents', 'assets', 'appointments'],
            'admin' => ['projects', 'tasks', 'parties', 'products', 'orders', 'documents', 'assets', 'appointments'],
            'sales' => ['parties', 'products', 'orders', 'documents'],
            'operator' => ['tasks', 'assets', 'appointments'],
            'administrator' => ['parties', 'products', 'orders', 'documents', 'appointments'],
        ];

        foreach ($matrix as $roleSlug => $modules) {
            if (! isset($roles[$roleSlug])) {
                continue;
            }

            $role = $roles[$roleSlug];

            foreach ($modules as $module) {
                $viewPerm = $permissions[$module.'.view'] ?? null;
                if ($viewPerm) {
                    $this->attachPermissionToRole($role->id, $viewPerm->id);
                }

                if (in_array($roleSlug, ['owner', 'admin', 'sales', 'administrator'], true)) {
                    $createPerm = $permissions[$module.'.create'] ?? null;
                    if ($createPerm) {
                        $this->attachPermissionToRole($role->id, $createPerm->id);
                    }
                }

                if (in_array($roleSlug, ['owner', 'admin', 'administrator'], true)) {
                    $updatePerm = $permissions[$module.'.update'] ?? null;
                    if ($updatePerm) {
                        $this->attachPermissionToRole($role->id, $updatePerm->id);
                    }
                }

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
