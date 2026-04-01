<?php

// database/seeders/Modules/TenantModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        $tenants = [];

        $tenants['tech'] = Tenant::firstOrCreate(
            ['slug' => 'tech-solutions-sa'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Tech Solutions SA',
                'settings' => [
                    'timezone' => 'America/Argentina/Salta',
                    'currency' => 'ARS',
                ],
            ]
        );

        $tenants['andina'] = Tenant::firstOrCreate(
            ['slug' => 'constructora-andina-srl'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Constructora Andina SRL',
                'settings' => [
                    'timezone' => 'America/Argentina/Salta',
                    'currency' => 'ARS',
                ],
            ]
        );

        $this->context['tenants'] = $tenants;
    }
}
