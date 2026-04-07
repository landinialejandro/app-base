<?php

// FILE: database/migrations/2026_04_07_220000_backfill_orders_allowed_kinds_constraints.php | V1

use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $capabilities = [
            CapabilityCatalog::VIEW_ANY,
            CapabilityCatalog::VIEW,
            CapabilityCatalog::CREATE,
            CapabilityCatalog::UPDATE,
            CapabilityCatalog::DELETE,
        ];

        $permissionSlugs = collect($capabilities)
            ->map(fn (string $capability) => "orders.$capability")
            ->all();

        $permissionsBySlug = DB::table('permissions')
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id', 'slug');

        $roles = DB::table('roles')
            ->select('id', 'tenant_id', 'slug')
            ->whereIn('slug', [
                RoleCatalog::OWNER,
                RoleCatalog::ADMIN,
                RoleCatalog::SALES,
                RoleCatalog::OPERATOR,
                RoleCatalog::ADMINISTRATOR,
            ])
            ->get();

        foreach ($roles as $role) {
            $contract = $this->orderContractForRole((string) $role->slug);

            foreach ($capabilities as $capability) {
                $permissionId = $permissionsBySlug["orders.$capability"] ?? null;

                if (! $permissionId) {
                    continue;
                }

                $meta = $contract[$capability] ?? null;

                if ($meta === null) {
                    DB::table('role_permission')
                        ->where('role_id', $role->id)
                        ->where('permission_id', $permissionId)
                        ->delete();

                    continue;
                }

                DB::table('role_permission')->updateOrInsert(
                    [
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'scope' => $meta['scope'],
                        'execution_mode' => 'manual',
                        'constraints' => json_encode([
                            'allowed_kinds' => $meta['allowed_kinds'],
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // No reversible de forma segura.
    }

    protected function orderContractForRole(string $roleSlug): array
    {
        return match ($roleSlug) {
            RoleCatalog::OWNER,
            RoleCatalog::ADMIN => $this->buildOrderContract(
                ['sale', 'purchase', 'service'],
                true
            ),

            RoleCatalog::SALES => $this->buildOrderContract(
                ['sale'],
                false
            ),

            RoleCatalog::OPERATOR => $this->buildOrderContract(
                ['service'],
                false
            ),

            RoleCatalog::ADMINISTRATOR => $this->buildOrderContract(
                ['sale', 'purchase'],
                true
            ),

            default => [],
        };
    }

    protected function buildOrderContract(array $allowedKinds, bool $allowDelete): array
    {
        $contract = [
            CapabilityCatalog::VIEW_ANY => [
                'scope' => PermissionScopeCatalog::TENANT_ALL,
                'allowed_kinds' => $allowedKinds,
            ],
            CapabilityCatalog::VIEW => [
                'scope' => PermissionScopeCatalog::TENANT_ALL,
                'allowed_kinds' => $allowedKinds,
            ],
            CapabilityCatalog::CREATE => [
                'scope' => null,
                'allowed_kinds' => $allowedKinds,
            ],
            CapabilityCatalog::UPDATE => [
                'scope' => PermissionScopeCatalog::TENANT_ALL,
                'allowed_kinds' => $allowedKinds,
            ],
        ];

        if ($allowDelete) {
            $contract[CapabilityCatalog::DELETE] = [
                'scope' => PermissionScopeCatalog::TENANT_ALL,
                'allowed_kinds' => $allowedKinds,
            ];
        }

        return $contract;
    }
};
