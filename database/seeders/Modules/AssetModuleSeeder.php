<?php

// FILE: database/seeders/Modules/AssetModuleSeeder.php | V2

namespace Database\Seeders\Modules;

use App\Models\Asset;
use Illuminate\Support\Collection;

class AssetModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants') || ! $this->hasDependency('parties')) {
            throw new \RuntimeException('AssetModuleSeeder requires tenants and parties');
        }

        $tenants = $this->getDependency('tenants');
        $parties = $this->getDependency('parties');

        $assets = [];
        $assets['tech'] = $this->createTechAssets(
            tenantId: $tenants['tech']->id,
            parties: $parties['techFixed']->merge($parties['techExtra'])
        );

        $assets['andina'] = $this->createAndinaAssets(
            tenantId: $tenants['andina']->id,
            parties: $parties['andinaFixed']->merge($parties['andinaExtra'])
        );

        $this->context['assets'] = $assets;
    }

    private function createTechAssets(string $tenantId, Collection $parties): Collection
    {
        return collect([
            $this->createAsset(
                tenantId: $tenantId,
                partyId: $parties->first()?->id,
                kind: 'vehicle',
                relationshipType: 'owned',
                name: 'Toyota Hilux',
                internalCode: 'TECH-VEH-001'
            ),
            $this->createAsset(
                tenantId: $tenantId,
                partyId: $parties->first()?->id,
                kind: 'vehicle',
                relationshipType: 'owned',
                name: 'Ford Ranger',
                internalCode: 'TECH-VEH-002'
            ),
            $this->createAsset(
                tenantId: $tenantId,
                partyId: $parties->skip(1)->first()?->id,
                kind: 'equipment',
                relationshipType: 'owned',
                name: 'Scanner OBD2',
                internalCode: 'TECH-EQP-001'
            ),
            $this->createAsset(
                tenantId: $tenantId,
                partyId: $parties->skip(1)->first()?->id,
                kind: 'equipment',
                relationshipType: 'owned',
                name: 'Elevador hidráulico',
                internalCode: 'TECH-EQP-002'
            ),
        ]);
    }

    private function createAndinaAssets(string $tenantId, Collection $parties): Collection
    {
        return collect([
            $this->createAsset(
                tenantId: $tenantId,
                partyId: $parties->first()?->id,
                kind: 'machinery',
                relationshipType: 'owned',
                name: 'Retroexcavadora',
                internalCode: 'AND-MAQ-001'
            ),
            $this->createAsset(
                tenantId: $tenantId,
                partyId: $parties->first()?->id,
                kind: 'machinery',
                relationshipType: 'owned',
                name: 'Camión volcador',
                internalCode: 'AND-MAQ-002'
            ),
            $this->createAsset(
                tenantId: $tenantId,
                partyId: $parties->skip(1)->first()?->id,
                kind: 'tool',
                relationshipType: 'owned',
                name: 'Mezcladora de cemento',
                internalCode: 'AND-TOL-001'
            ),
            $this->createAsset(
                tenantId: $tenantId,
                partyId: $parties->skip(1)->first()?->id,
                kind: 'tool',
                relationshipType: 'owned',
                name: 'Vibrador de concreto',
                internalCode: 'AND-TOL-002'
            ),
        ]);
    }

    private function createAsset(
        string $tenantId,
        ?int $partyId,
        string $kind,
        string $relationshipType,
        string $name,
        string $internalCode
    ): Asset {
        return Asset::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'internal_code' => $internalCode,
            ],
            [
                'party_id' => $partyId,
                'kind' => $kind,
                'relationship_type' => $relationshipType,
                'name' => $name,
                'status' => 'active',
                'notes' => 'Activo demo para pruebas del sistema.',
            ]
        );
    }
}
