<?php

// FILE: database/seeders/Modules/PartyModuleSeeder.php | V4

namespace Database\Seeders\Modules;

use App\Events\OperationalRecordCreated;
use App\Models\Party;
use App\Support\Catalogs\PartyCatalog;
use Illuminate\Support\Collection;

class PartyModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants')) {
            throw new \RuntimeException('PartyModuleSeeder requires tenants');
        }

        $tenants = $this->getDependency('tenants');
        $parties = [];

        $parties['techFixed'] = collect([
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenants['tech']->id, 'email' => 'contacto@acme.local'],
                    $this->getTechAcmeData()
                ),
                [PartyCatalog::ROLE_CUSTOMER]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenants['tech']->id, 'email' => 'laura@cliente.local'],
                    $this->getTechLauraData()
                ),
                [PartyCatalog::ROLE_CUSTOMER]
            ),
        ]);

        $parties['andinaFixed'] = collect([
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenants['andina']->id, 'email' => 'info@obraspat.local'],
                    $this->getAndinaObrasData()
                ),
                [PartyCatalog::ROLE_CUSTOMER]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenants['andina']->id, 'email' => 'marcos@obra.local'],
                    $this->getAndinaMarcosData()
                ),
                [PartyCatalog::ROLE_SUPPLIER]
            ),
        ]);

        $parties['techExtra'] = $this->generateAdditionalParties(
            tenant: $tenants['tech'],
            targetCount: (int) config('seeders.demo.tech.target_parties', 12)
        );

        $parties['andinaExtra'] = $this->generateAdditionalParties(
            tenant: $tenants['andina'],
            targetCount: (int) config('seeders.demo.andina.target_parties', 10)
        );

        $this->context['parties'] = $parties;
    }

    private function generateAdditionalParties($tenant, int $targetCount): Collection
    {
        $existingCount = Party::query()
            ->where('tenant_id', $tenant->id)
            ->count();

        $neededCount = max(0, $targetCount - $existingCount);

        $created = $neededCount > 0
            ? Party::factory()->count($neededCount)->create(['tenant_id' => $tenant->id])
            : collect();

        return $created->map(fn (Party $party) => $this->registerParty($party, [PartyCatalog::ROLE_OTHER]));
    }

    private function registerParty(Party $party, array $roles): Party
    {
        $validRoles = [];

        foreach (array_values(array_unique($roles)) as $role) {
            if (! in_array($role, PartyCatalog::roles(), true)) {
                continue;
            }

            $party->roles()->firstOrCreate([
                'tenant_id' => $party->tenant_id,
                'role' => $role,
            ]);

            $validRoles[] = $role;
        }

        if ($party->wasRecentlyCreated) {
            $this->emitPartyCreatedActivity($party, $validRoles);
        }

        return $party;
    }

    private function emitPartyCreatedActivity(Party $party, array $roles): void
    {
        $createdRoles = array_values(array_unique(array_filter(
            $roles,
            fn ($role) => is_string($role) && trim($role) !== ''
        )));

        sort($createdRoles);

        event(new OperationalRecordCreated(
            record: $party,
            actorUserId: null,
            metadata: [
                'party_roles' => [
                    'to' => $createdRoles,
                ],
            ],
        ));
    }

    private function getTechAcmeData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_COMPANY,
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
            'kind' => PartyCatalog::KIND_PERSON,
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
            'kind' => PartyCatalog::KIND_COMPANY,
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
            'kind' => PartyCatalog::KIND_PERSON,
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