<?php

// database/seeders/Modules/BranchModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Branch;

class BranchModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (!$this->hasDependency('tenants')) {
            throw new \RuntimeException('BranchModuleSeeder requires tenants');
        }

        $tenants = $this->getDependency('tenants');
        $branches = [];

        $branches['tech'] = collect([
            Branch::firstOrCreate(
                ['tenant_id' => $tenants['tech']->id, 'code' => 'CASA'],
                ['name' => 'Casa Central', 'address' => 'Neuquén Capital', 'city' => 'Neuquén']
            ),
            Branch::firstOrCreate(
                ['tenant_id' => $tenants['tech']->id, 'code' => 'TALL'],
                ['name' => 'Taller', 'address' => 'Centenario', 'city' => 'Centenario']
            ),
        ]);

        $branches['andina'] = collect([
            Branch::firstOrCreate(
                ['tenant_id' => $tenants['andina']->id, 'code' => 'OFIC'],
                ['name' => 'Oficina Central', 'address' => 'Neuquén Capital', 'city' => 'Neuquén']
            ),
            Branch::firstOrCreate(
                ['tenant_id' => $tenants['andina']->id, 'code' => 'OBRA'],
                ['name' => 'Base de Obra', 'address' => 'Añelo', 'city' => 'Añelo']
            ),
        ]);

        $this->context['branches'] = $branches;
    }
}