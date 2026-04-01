<?php

// database/seeders/Modules/AssetModuleSeeder.php

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

        // Tech assets
        $techParties = $parties['techFixed']->merge($parties['techExtra']);
        $assets['tech'] = $this->createAssets($tenants['tech']->id, $techParties, 'tech');

        // Andina assets
        $andinaParties = $parties['andinaFixed']->merge($parties['andinaExtra']);
        $assets['andina'] = $this->createAssets($tenants['andina']->id, $andinaParties, 'andina');

        $this->context['assets'] = $assets;
    }

    private function createAssets(string $tenantId, Collection $parties, string $type): Collection
    {
        $assets = collect();

        if ($type === 'tech') {
            // Vehículos
            $assets->push($this->createAsset($tenantId, $parties->first(), 'vehicle', 'owned', 'Toyota Hilux', 'AB-123-CD'));
            $assets->push($this->createAsset($tenantId, $parties->first(), 'vehicle', 'owned', 'Ford Ranger', 'EF-456-GH'));
            $assets->push($this->createAsset($tenantId, $parties->get(1), 'vehicle', 'leased', 'VW Amarok', 'IJ-789-KL'));

            // Equipos
            $assets->push($this->createAsset($tenantId, $parties->first(), 'equipment', 'owned', 'Scanner OBD2', 'SCAN-001'));
            $assets->push($this->createAsset($tenantId, $parties->first(), 'equipment', 'owned', 'Elevador hidráulico', 'ELEV-001'));
        } else {
            // Maquinaria construcción
            $assets->push($this->createAsset($tenantId, $parties->first(), 'machinery', 'owned', 'Retroexcavadora', 'RETRO-001'));
            $assets->push($this->createAsset($tenantId, $parties->first(), 'machinery', 'owned', 'Camión volcador', 'CAM-001'));
            $assets->push($this->createAsset($tenantId, $parties->get(1), 'machinery', 'leased', 'Grúa torre', 'GRUA-001'));

            // Herramientas
            $assets->push($this->createAsset($tenantId, $parties->first(), 'tool', 'owned', 'Mezcladora de cemento', 'MEZ-001'));
            $assets->push($this->createAsset($tenantId, $parties->first(), 'tool', 'owned', 'Vibrador de concreto', 'VIB-001'));
        }

        return $assets;
    }

    private function createAsset(
        string $tenantId,
        $party,
        string $kind,
        string $relationshipType,
        string $name,
        string $internalCode
    ): Asset {
        return Asset::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'internal_code' => $internalCode,
            ],
            [
                'party_id' => $party?->id,
                'kind' => $kind,
                'relationship_type' => $relationshipType,
                'name' => $name,
                'status' => 'active',
                'notes' => 'Activo de demostración',
            ]
        );
    }
}
