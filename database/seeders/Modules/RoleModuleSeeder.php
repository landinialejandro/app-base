<?php

// FILE: database/seeders/Modules/RoleModuleSeeder.php | V4

namespace Database\Seeders\Modules;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Catalogs\RoleCatalog;
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

        $roles['tech'] = $this->createBaseRoles($tenants['tech']->id);
        $roles['andina'] = $this->createBaseRoles($tenants['andina']->id);

        $this->assignPermissionsToRoles($roles['tech']);
        $this->assignPermissionsToRoles($roles['andina']);

        $this->context['roles'] = $roles;
    }

    private function createBaseRoles(string $tenantId): array
    {
        $roles = [];

        foreach ($this->roleDefinitions() as $definition) {
            $roles[$definition['slug']] = Role::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'slug' => $definition['slug'],
                ],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                ]
            );
        }

        return $roles;
    }

    private function roleDefinitions(): array
    {
        return [
            [
                'slug' => RoleCatalog::OWNER,
                'name' => RoleCatalog::label(RoleCatalog::OWNER, 'Propietario'),
                'description' => 'Propietario del tenant.',
            ],
            [
                'slug' => RoleCatalog::ADMIN,
                'name' => RoleCatalog::label(RoleCatalog::ADMIN, 'Administrador'),
                'description' => 'Administrador general.',
            ],
            [
                'slug' => RoleCatalog::SALES,
                'name' => RoleCatalog::label(RoleCatalog::SALES, 'Comercial'),
                'description' => 'Usuario comercial.',
            ],
            [
                'slug' => RoleCatalog::OPERATOR,
                'name' => RoleCatalog::label(RoleCatalog::OPERATOR, 'Operador'),
                'description' => 'Usuario operativo.',
            ],
            [
                'slug' => RoleCatalog::ADMINISTRATOR,
                'name' => RoleCatalog::label(RoleCatalog::ADMINISTRATOR, 'Administrativo'),
                'description' => 'Usuario administrativo.',
            ],
        ];
    }

    private function assignPermissionsToRoles(array $roles): void
    {
        $permissions = Permission::query()->get()->keyBy('slug');

        foreach ($this->permissionMatrix() as $roleSlug => $modules) {
            if (! isset($roles[$roleSlug])) {
                continue;
            }

            $role = $roles[$roleSlug];

            foreach ($modules as $module => $capabilities) {
                foreach ($capabilities as $capability => $scope) {
                    $permissionSlug = CapabilityCatalog::permissionSlug($module, $capability);
                    $permission = $permissions->get($permissionSlug);

                    if (! $permission) {
                        continue;
                    }

                    $this->attachPermissionToRole(
                        roleId: $role->id,
                        permissionId: $permission->id,
                        scope: $scope,
                        executionMode: 'manual',
                        constraints: []
                    );
                }
            }
        }
    }

    private function permissionMatrix(): array
    {
        $fullAccessModules = [
            ModuleCatalog::PARTIES,
            ModuleCatalog::PRODUCTS,
            ModuleCatalog::ASSETS,
            ModuleCatalog::ORDERS,
            ModuleCatalog::DOCUMENTS,
        ];

        return [
            RoleCatalog::OWNER => array_merge(
                $this->fullProjectPermissions(),
                $this->fullTaskPermissions(),
                $this->fullAppointmentPermissions(),
                $this->fullSharedModulePermissions($fullAccessModules)
            ),

            RoleCatalog::ADMIN => array_merge(
                $this->fullProjectPermissions(),
                $this->fullTaskPermissions(),
                $this->fullAppointmentPermissions(),
                $this->fullSharedModulePermissions($fullAccessModules)
            ),

            RoleCatalog::SALES => [
                ModuleCatalog::PROJECTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::LIMITED,
                ],
                ModuleCatalog::TASKS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::OWN_ASSIGNED,
                ],
                ModuleCatalog::PARTIES => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::PRODUCTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::ASSETS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::ORDERS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::DOCUMENTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::APPOINTMENTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::OWN_ASSIGNED,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::OWN_ASSIGNED,
                ],
            ],

            RoleCatalog::OPERATOR => [
                ModuleCatalog::PROJECTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::LIMITED,
                ],
                ModuleCatalog::TASKS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::OWN_ASSIGNED,
                ],
                ModuleCatalog::PARTIES => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::PRODUCTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::ASSETS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::ORDERS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::OWN_ASSIGNED,
                ],
                ModuleCatalog::DOCUMENTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::APPOINTMENTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::OWN_ASSIGNED,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::OWN_ASSIGNED,
                ],
            ],

            RoleCatalog::ADMINISTRATOR => [
                ModuleCatalog::PROJECTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::LIMITED,
                ],
                ModuleCatalog::TASKS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::PARTIES => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::PRODUCTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::ASSETS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::ORDERS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::DOCUMENTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                ],
                ModuleCatalog::APPOINTMENTS => [
                    CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                    CapabilityCatalog::CREATE => null,
                    CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                ],
            ],
        ];
    }

    private function fullProjectPermissions(): array
    {
        return [
            ModuleCatalog::PROJECTS => [
                CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::CREATE => null,
                CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::DELETE => PermissionScopeCatalog::TENANT_ALL,
            ],
        ];
    }

    private function fullTaskPermissions(): array
    {
        return [
            ModuleCatalog::TASKS => [
                CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::CREATE => null,
                CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::DELETE => PermissionScopeCatalog::TENANT_ALL,
            ],
        ];
    }

    private function fullAppointmentPermissions(): array
    {
        return [
            ModuleCatalog::APPOINTMENTS => [
                CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::CREATE => null,
                CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::DELETE => PermissionScopeCatalog::TENANT_ALL,
            ],
        ];
    }

    private function fullSharedModulePermissions(array $modules): array
    {
        $matrix = [];

        foreach ($modules as $module) {
            $matrix[$module] = [
                CapabilityCatalog::VIEW_ANY => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::CREATE => null,
                CapabilityCatalog::UPDATE => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::DELETE => PermissionScopeCatalog::TENANT_ALL,
            ];
        }

        return $matrix;
    }

    private function attachPermissionToRole(
        int $roleId,
        int $permissionId,
        ?string $scope = null,
        string $executionMode = 'manual',
        array $constraints = []
    ): void {
        DB::table('role_permission')->updateOrInsert(
            [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ],
            [
                'scope' => $scope,
                'execution_mode' => $executionMode,
                'constraints' => empty($constraints) ? null : json_encode($constraints, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
