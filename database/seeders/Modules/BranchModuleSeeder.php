<?php

// FILE: database/seeders/Modules/BranchModuleSeeder.php | V2

namespace Database\Seeders\Modules;

use App\Models\Branch;
use Illuminate\Support\Collection;

class BranchModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants')) {
            throw new \RuntimeException('BranchModuleSeeder requires tenants');
        }

        $tenants = $this->getDependency('tenants');
        $branches = [];

        $branches['tech'] = $this->createBranches($tenants['tech']->id, [
            [
                'code' => 'CASA',
                'name' => 'Casa Central',
                'address' => 'Neuquén Capital',
                'city' => 'Neuquén',
            ],
            [
                'code' => 'TALL',
                'name' => 'Taller',
                'address' => 'Centenario',
                'city' => 'Centenario',
            ],
        ]);

        $branches['andina'] = $this->createBranches($tenants['andina']->id, [
            [
                'code' => 'OFIC',
                'name' => 'Oficina Central',
                'address' => 'Neuquén Capital',
                'city' => 'Neuquén',
            ],
            [
                'code' => 'OBRA',
                'name' => 'Base de Obra',
                'address' => 'Añelo',
                'city' => 'Añelo',
            ],
        ]);

        $this->context['branches'] = $branches;
    }

    private function createBranches(string $tenantId, array $definitions): Collection
    {
        $branches = collect();

        foreach ($definitions as $definition) {
            $branches->push(
                Branch::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'code' => $definition['code'],
                    ],
                    [
                        'name' => $definition['name'],
                        'address' => $definition['address'],
                        'city' => $definition['city'],
                    ]
                )
            );
        }

        return $branches->values();
    }
}
