<?php

// FILE: database/seeders/Modules/TenantModuleSeeder.php | V3

namespace Database\Seeders\Modules;

use App\Models\Tenant;
use App\Support\Catalogs\ModuleCatalog;
use Illuminate\Support\Str;

class TenantModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        $tenants = [];

        $tenants['tech'] = Tenant::updateOrCreate(
            ['slug' => 'tech-solutions-sa'],
            [
                'id' => $this->resolveTenantId('tech-solutions-sa'),
                'name' => 'Tech Solutions SA',
                'settings' => $this->buildTenantSettings('tech'),
            ]
        );

        $tenants['andina'] = Tenant::updateOrCreate(
            ['slug' => 'constructora-andina-srl'],
            [
                'id' => $this->resolveTenantId('constructora-andina-srl'),
                'name' => 'Constructora Andina SRL',
                'settings' => $this->buildTenantSettings('andina'),
            ]
        );

        $tenants['lavadero'] = Tenant::updateOrCreate(
            ['slug' => 'lavadero-sa'],
            [
                'id' => $this->resolveTenantId('lavadero-sa'),
                'name' => 'Lavadero SA',
                'settings' => $this->buildTenantSettings('lavadero'),
            ]
        );

        $this->context['tenants'] = $tenants;
    }

    private function resolveTenantId(string $slug): string
    {
        $existingId = Tenant::query()
            ->where('slug', $slug)
            ->value('id');

        return $existingId ?: (string) Str::uuid();
    }

    private function buildTenantSettings(string $tenantKey): array
    {
        return [
            'timezone' => 'America/Argentina/Salta',
            'currency' => 'ARS',
            'business_profile' => [
                'type' => match ($tenantKey) {
                    'tech' => 'workshop',
                    'andina' => 'construction',
                    'lavadero' => 'car_wash',
                    default => 'general',
                },
            ],
            'module_access' => [
                'enabled_modules' => $this->enabledModulesForTenant($tenantKey),
            ],
        ];
    }

    private function enabledModulesForTenant(string $tenantKey): array
    {
        return collect(ModuleCatalog::all())
            ->reject(fn (string $module) => $module === ModuleCatalog::DASHBOARD)
            ->mapWithKeys(fn (string $module) => [$module => true])
            ->all();
    }
}