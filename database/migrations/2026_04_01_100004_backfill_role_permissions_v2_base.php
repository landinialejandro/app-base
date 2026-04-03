<?php

// FILE: database/migrations/2026_04_01_100004_backfill_role_permissions_v2_base.php | V3

use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $tenants = DB::table('tenants')->select('id')->get();

        $roleLabels = RoleCatalog::labels();

        foreach ($tenants as $tenant) {
            foreach (RoleCatalog::all() as $roleSlug) {
                DB::table('roles')->updateOrInsert(
                    [
                        'tenant_id' => $tenant->id,
                        'slug' => $roleSlug,
                    ],
                    [
                        'name' => $roleLabels[$roleSlug] ?? ucfirst($roleSlug),
                        'description' => null,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        foreach ($this->permissionDefinitions() as $definition) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'group' => $definition['group'],
                    'description' => $definition['description'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $rolesByTenantAndSlug = DB::table('roles')
            ->select('id', 'tenant_id', 'slug')
            ->get()
            ->groupBy('tenant_id')
            ->map(fn ($rows) => $rows->keyBy('slug'));

        $permissionsBySlug = DB::table('permissions')
            ->select('id', 'slug')
            ->get()
            ->keyBy('slug');

        foreach ($tenants as $tenant) {
            $tenantRoles = $rolesByTenantAndSlug[$tenant->id] ?? collect();

            foreach ($this->matrix() as $module => $roles) {
                foreach ($roles as $roleSlug => $capabilities) {
                    $role = $tenantRoles[$roleSlug] ?? null;

                    if (! $role) {
                        continue;
                    }

                    foreach ($capabilities as $capability => $meta) {
                        if (($meta['allowed'] ?? false) !== true) {
                            continue;
                        }

                        $permissionSlug = CapabilityCatalog::permissionSlug($module, $capability);
                        $permission = $permissionsBySlug[$permissionSlug] ?? null;

                        if (! $permission) {
                            continue;
                        }

                        DB::table('role_permission')->updateOrInsert(
                            [
                                'role_id' => $role->id,
                                'permission_id' => $permission->id,
                            ],
                            [
                                'scope' => $meta['scope'] ?? null,
                                'execution_mode' => $meta['execution_mode'] ?? null,
                                'constraints' => ! empty($meta['constraints'])
                                    ? json_encode($meta['constraints'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                    : null,
                                'updated_at' => $now,
                                'created_at' => $now,
                            ]
                        );
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $permissionSlugs = collect($this->permissionDefinitions())
            ->pluck('slug')
            ->all();

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        if (! empty($permissionIds)) {
            DB::table('role_permission')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            if (DB::getSchemaBuilder()->hasTable('membership_permission_overrides')) {
                DB::table('membership_permission_overrides')
                    ->whereIn('permission_id', $permissionIds)
                    ->delete();
            }

            DB::table('permissions')
                ->whereIn('id', $permissionIds)
                ->delete();
        }
    }

    protected function permissionDefinitions(): array
    {
        $definitions = [];

        foreach ($this->matrix() as $module => $capabilitiesByRole) {
            $capabilities = collect($capabilitiesByRole)
                ->flatMap(fn (array $items) => array_keys($items))
                ->unique()
                ->values()
                ->all();

            foreach ($capabilities as $capability) {
                $definitions[] = [
                    'name' => ModuleCatalog::label($module).' · '.CapabilityCatalog::label($capability, $capability),
                    'slug' => CapabilityCatalog::permissionSlug($module, $capability),
                    'group' => $module,
                    'description' => null,
                ];
            }
        }

        return $definitions;
    }

    protected function matrix(): array
    {
        return [
            ModuleCatalog::PROJECTS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                ],
            ],

            ModuleCatalog::TASKS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                ],
            ],

            ModuleCatalog::APPOINTMENTS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'scope' => 'own_assigned', 'execution_mode' => 'manual'],
                ],
            ],

            ModuleCatalog::PARTIES => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
            ],

            ModuleCatalog::PRODUCTS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
            ],

            ModuleCatalog::ASSETS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
            ],

            ModuleCatalog::ORDERS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
            ],

            ModuleCatalog::DOCUMENTS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::DELETE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::CREATE => ['allowed' => true, 'execution_mode' => 'manual'],
                    CapabilityCatalog::UPDATE => ['allowed' => true, 'execution_mode' => 'manual'],
                ],
            ],

            ModuleCatalog::DASHBOARD => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW_ANALYTICS => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                    CapabilityCatalog::VIEW_ANALYTICS => ['allowed' => true, 'scope' => 'tenant_all', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'limited', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'limited', 'execution_mode' => 'manual'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW => ['allowed' => true, 'scope' => 'limited', 'execution_mode' => 'manual'],
                ],
            ],
        ];
    }
};
