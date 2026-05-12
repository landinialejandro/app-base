<?php

// FILE: database/seeders/Modules/PartyModuleSeeder.php | V5

namespace Database\Seeders\Modules;

use App\Events\OperationalRecordCreated;
use App\Models\Party;
use App\Support\Catalogs\PartyCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        $parties['lavaderoFixed'] = $this->createLavaderoParties($tenants['lavadero']);

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

    private function createLavaderoParties($tenant): Collection
    {
        $parties = collect([
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => 'santiago.mendez@lavaderosa.local'],
                    $this->getLavaderoSantiagoData()
                ),
                [PartyCatalog::ROLE_EMPLOYEE]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => 'laura.ferreyra@lavaderosa.local'],
                    $this->getLavaderoLauraData()
                ),
                [PartyCatalog::ROLE_EMPLOYEE]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => 'martin.aguirre@lavaderosa.local'],
                    $this->getLavaderoMartinData()
                ),
                [PartyCatalog::ROLE_EMPLOYEE]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => 'carolina.torres@lavaderosa.local'],
                    $this->getLavaderoCarolinaData()
                ),
                [PartyCatalog::ROLE_EMPLOYEE]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => 'flota@remisesnorte.local'],
                    $this->getLavaderoRemisesNorteData()
                ),
                [PartyCatalog::ROLE_CUSTOMER]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => 'administracion@deliverysur.local'],
                    $this->getLavaderoDeliverySurData()
                ),
                [PartyCatalog::ROLE_CUSTOMER]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => 'mariela.rios@cliente.local'],
                    $this->getLavaderoMarielaData()
                ),
                [PartyCatalog::ROLE_CUSTOMER]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => 'ventas@quimicadelvalle.local'],
                    $this->getLavaderoQuimicaDelValleData()
                ),
                [PartyCatalog::ROLE_SUPPLIER]
            ),
            $this->registerParty(
                Party::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => 'pedidos@patagonialimpieza.local'],
                    $this->getLavaderoPatagoniaLimpiezaData()
                ),
                [PartyCatalog::ROLE_SUPPLIER]
            ),
        ]);

        $this->linkMembershipParty($tenant->id, 'santiago.mendez@lavaderosa.local', $parties[0]);
        $this->linkMembershipParty($tenant->id, 'laura.ferreyra@lavaderosa.local', $parties[1]);
        $this->linkMembershipParty($tenant->id, 'martin.aguirre@lavaderosa.local', $parties[2]);
        $this->linkMembershipParty($tenant->id, 'carolina.torres@lavaderosa.local', $parties[3]);

        return $parties;
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

    private function linkMembershipParty(string $tenantId, string $userEmail, Party $party): void
    {
        $membershipId = DB::table('memberships')
            ->join('users', 'users.id', '=', 'memberships.user_id')
            ->where('memberships.tenant_id', $tenantId)
            ->where('users.email', $userEmail)
            ->value('memberships.id');

        if (! $membershipId) {
            return;
        }

        DB::table('memberships')
            ->where('id', $membershipId)
            ->update([
                'party_id' => $party->id,
                'updated_at' => now(),
            ]);
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

    private function getLavaderoSantiagoData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_PERSON,
            'name' => 'Santiago Méndez',
            'display_name' => 'Santiago Méndez',
            'document_type' => 'DNI',
            'document_number' => '28673491',
            'phone' => '299-456-7821',
            'address' => 'Cipolletti, Río Negro',
            'notes' => 'Responsable general y fundador de Lavadero SA.',
            'is_active' => true,
        ];
    }

    private function getLavaderoLauraData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_PERSON,
            'name' => 'Laura Ferreyra',
            'display_name' => 'Laura Ferreyra',
            'document_type' => 'DNI',
            'document_number' => '31458276',
            'phone' => '299-456-7822',
            'address' => 'Cipolletti, Río Negro',
            'notes' => 'Administradora operativa del local.',
            'is_active' => true,
        ];
    }

    private function getLavaderoMartinData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_PERSON,
            'name' => 'Martín Aguirre',
            'display_name' => 'Martín Aguirre',
            'document_type' => 'DNI',
            'document_number' => '33781952',
            'phone' => '299-456-7823',
            'address' => 'General Roca, Río Negro',
            'notes' => 'Responsable comercial y atención de cuentas frecuentes.',
            'is_active' => true,
        ];
    }

    private function getLavaderoCarolinaData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_PERSON,
            'name' => 'Carolina Torres',
            'display_name' => 'Carolina Torres',
            'document_type' => 'DNI',
            'document_number' => '35194728',
            'phone' => '299-456-7824',
            'address' => 'Fernández Oro, Río Negro',
            'notes' => 'Gestión administrativa, control de cobros y conciliaciones.',
            'is_active' => true,
        ];
    }

    private function getLavaderoRemisesNorteData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_COMPANY,
            'name' => 'Remises Norte SRL',
            'display_name' => 'Remises Norte',
            'document_type' => 'CUIT',
            'document_number' => '30-71824563-4',
            'tax_id' => '30-71824563-4',
            'phone' => '299-456-7901',
            'address' => 'Cipolletti, Río Negro',
            'notes' => 'Cliente frecuente con flota de vehículos livianos.',
            'is_active' => true,
        ];
    }

    private function getLavaderoDeliverySurData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_COMPANY,
            'name' => 'Delivery Sur SRL',
            'display_name' => 'Delivery Sur',
            'document_type' => 'CUIT',
            'document_number' => '30-71590218-6',
            'tax_id' => '30-71590218-6',
            'phone' => '299-456-7902',
            'address' => 'Neuquén Capital',
            'notes' => 'Cliente comercial con vehículos de reparto urbano.',
            'is_active' => true,
        ];
    }

    private function getLavaderoMarielaData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_PERSON,
            'name' => 'Mariela Ríos',
            'display_name' => 'Mariela Ríos',
            'document_type' => 'DNI',
            'document_number' => '29876145',
            'phone' => '299-456-7903',
            'address' => 'Cipolletti, Río Negro',
            'notes' => 'Cliente particular frecuente del autoservicio.',
            'is_active' => true,
        ];
    }

    private function getLavaderoQuimicaDelValleData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_COMPANY,
            'name' => 'Química del Valle SRL',
            'display_name' => 'Química del Valle',
            'document_type' => 'CUIT',
            'document_number' => '30-70981234-8',
            'tax_id' => '30-70981234-8',
            'phone' => '299-456-7951',
            'address' => 'Parque Industrial Neuquén',
            'notes' => 'Proveedor de shampoo espumante, cera líquida y químicos de lavado.',
            'is_active' => true,
        ];
    }

    private function getLavaderoPatagoniaLimpiezaData(): array
    {
        return [
            'kind' => PartyCatalog::KIND_COMPANY,
            'name' => 'Patagonia Limpieza Mayorista',
            'display_name' => 'Patagonia Limpieza',
            'document_type' => 'CUIT',
            'document_number' => '30-71145690-2',
            'tax_id' => '30-71145690-2',
            'phone' => '299-456-7952',
            'address' => 'General Roca, Río Negro',
            'notes' => 'Proveedor de microfibras, aromatizadores e insumos de limpieza.',
            'is_active' => true,
        ];
    }
}