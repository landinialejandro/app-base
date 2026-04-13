<?php

// FILE: database/seeders/Modules/RolePermissionModuleSeeder.php | V4

namespace Database\Seeders\Modules;

use App\Models\Permission;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\PartyCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Support\Facades\DB;

class RolePermissionModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants')) {
            throw new \RuntimeException('RolePermissionModuleSeeder requires tenants');
        }

        $now = now();

        $permissionsBySlug = Permission::query()
            ->get()
            ->keyBy('slug');

        $rolesByTenant = DB::table('roles')
            ->select('id', 'tenant_id', 'slug')
            ->get()
            ->groupBy('tenant_id')
            ->map(fn ($rows) => $rows->keyBy('slug'));

        DB::table('role_permission')->delete();

        foreach ($this->getDependency('tenants') as $tenant) {
            $tenantRoles = $rolesByTenant[$tenant->id] ?? collect();

            foreach ($this->matrix() as $module => $roles) {
                foreach ($roles as $roleSlug => $capabilities) {
                    $role = $tenantRoles[$roleSlug] ?? null;

                    if (! $role) {
                        continue;
                    }

                    foreach ($capabilities as $capability => $meta) {
                        $permissionSlug = CapabilityCatalog::permissionSlug($module, $capability);
                        $permission = $permissionsBySlug->get($permissionSlug);

                        if (! $permission) {
                            continue;
                        }

                        DB::table('role_permission')->insert([
                            'role_id' => $role->id,
                            'permission_id' => $permission->id,
                            'scope' => $meta['scope'],
                            'execution_mode' => 'manual',
                            'constraints' => empty($meta['constraints'])
                                ? null
                                : json_encode($meta['constraints'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    protected function matrix(): array
    {
        $allPartyKinds = array_keys(PartyCatalog::kindLabels());

        return [
            ModuleCatalog::DASHBOARD => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => [
                        'scope' => PermissionScopeCatalog::TENANT_ALL,
                    ],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => [
                        'scope' => PermissionScopeCatalog::TENANT_ALL,
                    ],
                ],
            ],

            ModuleCatalog::PROJECTS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::LIMITED],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::LIMITED],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::LIMITED],
                ],
            ],

            ModuleCatalog::TASKS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::LIMITED],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                ],
            ],

            ModuleCatalog::APPOINTMENTS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::OWN_ASSIGNED],
                ],
            ],

            ModuleCatalog::PARTIES => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, $allPartyKinds),
                    CapabilityCatalog::VIEW => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, $allPartyKinds),
                    CapabilityCatalog::CREATE => $this->partyMeta(null, $allPartyKinds),
                    CapabilityCatalog::UPDATE => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, $allPartyKinds),
                    CapabilityCatalog::DELETE => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, $allPartyKinds),
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, $allPartyKinds),
                    CapabilityCatalog::VIEW => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, $allPartyKinds),
                    CapabilityCatalog::CREATE => $this->partyMeta(null, $allPartyKinds),
                    CapabilityCatalog::UPDATE => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, $allPartyKinds),
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::VIEW => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::CREATE => $this->partyMeta(null, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::UPDATE => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::VIEW => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::CREATE => $this->partyMeta(null, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::UPDATE => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_SUPPLIER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::VIEW => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_SUPPLIER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::CREATE => $this->partyMeta(null, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_SUPPLIER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::UPDATE => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_SUPPLIER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                    CapabilityCatalog::DELETE => $this->partyMeta(PermissionScopeCatalog::TENANT_ALL, [
                        PartyCatalog::KIND_CUSTOMER,
                        PartyCatalog::KIND_SUPPLIER,
                        PartyCatalog::KIND_PERSON,
                        PartyCatalog::KIND_COMPANY,
                    ]),
                ],
            ],

            ModuleCatalog::PRODUCTS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
            ],

            ModuleCatalog::ASSETS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
            ],

            ModuleCatalog::ORDERS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::VIEW => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::CREATE => $this->orderMeta(null, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::UPDATE => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::DELETE => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::VIEW => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::CREATE => $this->orderMeta(null, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::UPDATE => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::DELETE => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                        OrderCatalog::KIND_SERVICE,
                    ]),
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                    ]),
                    CapabilityCatalog::VIEW => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                    ]),
                    CapabilityCatalog::CREATE => $this->orderMeta(null, [
                        OrderCatalog::KIND_SALE,
                    ]),
                    CapabilityCatalog::UPDATE => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                    ]),
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::VIEW => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::CREATE => $this->orderMeta(null, [
                        OrderCatalog::KIND_SERVICE,
                    ]),
                    CapabilityCatalog::UPDATE => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SERVICE,
                    ]),
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                    ]),
                    CapabilityCatalog::VIEW => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                    ]),
                    CapabilityCatalog::CREATE => $this->orderMeta(null, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                    ]),
                    CapabilityCatalog::UPDATE => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                    ]),
                    CapabilityCatalog::DELETE => $this->orderMeta(PermissionScopeCatalog::TENANT_ALL, [
                        OrderCatalog::KIND_SALE,
                        OrderCatalog::KIND_PURCHASE,
                    ]),
                ],
            ],

            ModuleCatalog::DOCUMENTS => [
                RoleCatalog::OWNER => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::ADMIN => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::SALES => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::OPERATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    CapabilityCatalog::VIEW_ANY => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::VIEW => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::CREATE => ['scope' => null],
                    CapabilityCatalog::UPDATE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                    CapabilityCatalog::DELETE => ['scope' => PermissionScopeCatalog::TENANT_ALL],
                ],
            ],
        ];
    }

    protected function orderMeta(?string $scope, array $allowedKinds): array
    {
        return [
            'scope' => $scope,
            'constraints' => [
                'allowed_kinds' => $allowedKinds,
            ],
        ];
    }

    protected function partyMeta(?string $scope, array $allowedKinds): array
    {
        return [
            'scope' => $scope,
            'constraints' => [
                'allowed_kinds' => $allowedKinds,
            ],
        ];
    }
}
