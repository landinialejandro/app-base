<?php

// database/seeders/Modules/PartyModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Party;

class PartyModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants')) {
            throw new \RuntimeException('PartyModuleSeeder requires tenants');
        }

        $tenants = $this->getDependency('tenants');
        $parties = [];

        // Tech fixed parties
        $parties['techFixed'] = collect([
            Party::firstOrCreate(
                ['tenant_id' => $tenants['tech']->id, 'email' => 'contacto@acme.local'],
                $this->getTechAcmeData()
            ),
            Party::firstOrCreate(
                ['tenant_id' => $tenants['tech']->id, 'email' => 'laura@cliente.local'],
                $this->getTechLauraData()
            ),
        ]);

        // Andina fixed parties
        $parties['andinaFixed'] = collect([
            Party::firstOrCreate(
                ['tenant_id' => $tenants['andina']->id, 'email' => 'info@obraspat.local'],
                $this->getAndinaObrasData()
            ),
            Party::firstOrCreate(
                ['tenant_id' => $tenants['andina']->id, 'email' => 'marcos@obra.local'],
                $this->getAndinaMarcosData()
            ),
        ]);

        // Generate additional parties if needed
        $parties['techExtra'] = $this->generateAdditionalParties($tenants['tech'], 12);
        $parties['andinaExtra'] = $this->generateAdditionalParties($tenants['andina'], 10);

        $this->context['parties'] = $parties;
    }

    private function generateAdditionalParties($tenant, int $targetCount): Collection
    {
        $existingCount = Party::where('tenant_id', $tenant->id)->count();
        $neededCount = max(0, $targetCount - $existingCount);

        return $neededCount > 0
            ? Party::factory()->count($neededCount)->create(['tenant_id' => $tenant->id])
            : collect();
    }

    private function getTechAcmeData(): array
    {
        return [
            'kind' => 'company',
            'name' => 'Empresa ACME',
            'display_name' => 'ACME',
            'document_type' => 'CUIT',
            'document_number' => '30-12345678-9',
            'tax_id' => '30-12345678-9',
            'phone' => '299-555-1001',
            'address' => 'Neuquén Capital',
            'notes' => 'Cliente estratégico.',
            'is_active' => true,
        ];
    }

    private function getTechLauraData(): array
    {
        return [
            'kind' => 'person',
            'name' => 'Laura Fernández',
            'display_name' => 'Laura Fernández',
            'document_type' => 'DNI',
            'document_number' => '27123456',
            'phone' => '299-555-1004',
            'address' => 'Centenario, Neuquén',
            'notes' => 'Contacto operativo.',
            'is_active' => true,
        ];
    }

    private function getAndinaObrasData(): array
    {
        return [
            'kind' => 'company',
            'name' => 'Obras Patagónicas',
            'display_name' => 'Obras Patagónicas',
            'document_type' => 'CUIT',
            'document_number' => '30-42345678-3',
            'tax_id' => '30-42345678-3',
            'phone' => '299-555-2001',
            'address' => 'Neuquén Capital',
            'notes' => 'Cliente de obras privadas.',
            'is_active' => true,
        ];
    }

    private function getAndinaMarcosData(): array
    {
        return [
            'kind' => 'person',
            'name' => 'Marcos Quiroga',
            'display_name' => 'Marcos Quiroga',
            'document_type' => 'DNI',
            'document_number' => '30111222',
            'phone' => '299-555-2003',
            'address' => 'Añelo, Neuquén',
            'notes' => 'Supervisor externo.',
            'is_active' => true,
        ];
    }
}
