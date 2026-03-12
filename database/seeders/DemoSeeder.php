<?php

// FILE: database/seeders/DemoSeeder.php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Order;
use App\Models\Party;
use App\Models\Product;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            /*
            |--------------------------------------------------------------------------
            | TENANTS
            |--------------------------------------------------------------------------
            */

            $tenantTech = Tenant::firstOrCreate(
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

            $tenantAndina = Tenant::firstOrCreate(
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

            /*
            |--------------------------------------------------------------------------
            | USERS
            |--------------------------------------------------------------------------
            | password para todos: password
            |--------------------------------------------------------------------------
            */

            $ownerTech = User::firstOrCreate(
                ['email' => 'juan@tech.local'],
                ['name' => 'Juan Tech', 'password' => 'password']
            );

            $ownerAndina = User::firstOrCreate(
                ['email' => 'maria@andina.local'],
                ['name' => 'María Andina', 'password' => 'password']
            );

            $sharedUser = User::firstOrCreate(
                ['email' => 'carlos@demo.local'],
                ['name' => 'Carlos Operaciones', 'password' => 'password']
            );

            $techUser = User::firstOrCreate(
                ['email' => 'ana@demo.local'],
                ['name' => 'Ana Comercial', 'password' => 'password']
            );

            $andinaUser = User::firstOrCreate(
                ['email' => 'pedro@demo.local'],
                ['name' => 'Pedro Obra', 'password' => 'password']
            );

            /*
            |--------------------------------------------------------------------------
            | MEMBERSHIPS
            |--------------------------------------------------------------------------
            */

            $this->createMembership($tenantTech, $ownerTech, true);
            $this->createMembership($tenantTech, $sharedUser, false);
            $this->createMembership($tenantTech, $techUser, false);

            $this->createMembership($tenantAndina, $ownerAndina, true);
            $this->createMembership($tenantAndina, $sharedUser, false);
            $this->createMembership($tenantAndina, $andinaUser, false);

            /*
            |--------------------------------------------------------------------------
            | BRANCHES
            |--------------------------------------------------------------------------
            */

            $techBranches = collect([
                Branch::firstOrCreate(
                    ['tenant_id' => $tenantTech->id, 'code' => 'CASA'],
                    [
                        'name' => 'Casa Central',
                        'address' => 'Neuquén Capital',
                        'city' => 'Neuquén',
                    ]
                ),
                Branch::firstOrCreate(
                    ['tenant_id' => $tenantTech->id, 'code' => 'TALL'],
                    [
                        'name' => 'Taller',
                        'address' => 'Centenario',
                        'city' => 'Centenario',
                    ]
                ),
            ]);

            $andinaBranches = collect([
                Branch::firstOrCreate(
                    ['tenant_id' => $tenantAndina->id, 'code' => 'OFIC'],
                    [
                        'name' => 'Oficina Central',
                        'address' => 'Neuquén Capital',
                        'city' => 'Neuquén',
                    ]
                ),
                Branch::firstOrCreate(
                    ['tenant_id' => $tenantAndina->id, 'code' => 'OBRA'],
                    [
                        'name' => 'Base de Obra',
                        'address' => 'Añelo',
                        'city' => 'Añelo',
                    ]
                ),
            ]);

            /*
            |--------------------------------------------------------------------------
            | ROLES
            |--------------------------------------------------------------------------
            */

            $this->createRole($tenantTech, 'Owner', 'owner', 'Propietario del tenant.');
            $this->createRole($tenantTech, 'Admin', 'admin', 'Administrador general.');
            $this->createRole($tenantTech, 'Operador', 'operator', 'Usuario operativo.');
            $this->createRole($tenantTech, 'Comercial', 'sales', 'Usuario comercial.');

            $this->createRole($tenantAndina, 'Owner', 'owner', 'Propietario del tenant.');
            $this->createRole($tenantAndina, 'Admin', 'admin', 'Administrador general.');
            $this->createRole($tenantAndina, 'Operador', 'operator', 'Usuario operativo.');
            $this->createRole($tenantAndina, 'Obra', 'site', 'Usuario de obra.');

            /*
            |--------------------------------------------------------------------------
            | INVITATIONS
            |--------------------------------------------------------------------------
            */

            $this->createInvitation($tenantTech, 'nuevo-admin@tech.local');
            $this->createInvitation($tenantTech, 'nuevo-operador@tech.local');

            $this->createInvitation($tenantAndina, 'nuevo-admin@andina.local');
            $this->createInvitation($tenantAndina, 'nuevo-obra@andina.local');

            /*
            |--------------------------------------------------------------------------
            | PARTIES FIJOS
            |--------------------------------------------------------------------------
            */

            $acme = Party::firstOrCreate(
                ['tenant_id' => $tenantTech->id, 'email' => 'contacto@acme.local'],
                [
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
                ]
            );

            $laura = Party::firstOrCreate(
                ['tenant_id' => $tenantTech->id, 'email' => 'laura@cliente.local'],
                [
                    'kind' => 'person',
                    'name' => 'Laura Fernández',
                    'display_name' => 'Laura Fernández',
                    'document_type' => 'DNI',
                    'document_number' => '27123456',
                    'tax_id' => null,
                    'phone' => '299-555-1004',
                    'address' => 'Centenario, Neuquén',
                    'notes' => 'Contacto operativo.',
                    'is_active' => true,
                ]
            );

            $obrasPatagonicas = Party::firstOrCreate(
                ['tenant_id' => $tenantAndina->id, 'email' => 'info@obraspat.local'],
                [
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
                ]
            );

            $marcos = Party::firstOrCreate(
                ['tenant_id' => $tenantAndina->id, 'email' => 'marcos@obra.local'],
                [
                    'kind' => 'person',
                    'name' => 'Marcos Quiroga',
                    'display_name' => 'Marcos Quiroga',
                    'document_type' => 'DNI',
                    'document_number' => '30111222',
                    'tax_id' => null,
                    'phone' => '299-555-2003',
                    'address' => 'Añelo, Neuquén',
                    'notes' => 'Supervisor externo.',
                    'is_active' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | PARTIES VOLUMEN
            |--------------------------------------------------------------------------
            */

            $techExistingParties = Party::where('tenant_id', $tenantTech->id)->count();
            $andinaExistingParties = Party::where('tenant_id', $tenantAndina->id)->count();

            $techParties = $techExistingParties < 12
                ? Party::factory()->count(12 - $techExistingParties)->create(['tenant_id' => $tenantTech->id])
                : collect();

            $andinaParties = $andinaExistingParties < 10
                ? Party::factory()->count(10 - $andinaExistingParties)->create(['tenant_id' => $tenantAndina->id])
                : collect();

            /*
            |--------------------------------------------------------------------------
            | PROJECTS
            |--------------------------------------------------------------------------
            */

            $erpProject = Project::firstOrCreate(
                ['tenant_id' => $tenantTech->id, 'name' => 'Implementación ERP'],
                ['description' => 'Implementación inicial del sistema.']
            );

            $migrationProject = Project::firstOrCreate(
                ['tenant_id' => $tenantTech->id, 'name' => 'Migración Base de Datos'],
                ['description' => 'Migración del sistema legado.']
            );

            $buildingProject = Project::firstOrCreate(
                ['tenant_id' => $tenantAndina->id, 'name' => 'Edificio Central'],
                ['description' => 'Proyecto principal de obra.']
            );

            $reviewProject = Project::firstOrCreate(
                ['tenant_id' => $tenantAndina->id, 'name' => 'Revisión Estructural'],
                ['description' => 'Validación técnica documental.']
            );

            $techExistingProjects = Project::where('tenant_id', $tenantTech->id)->count();
            $andinaExistingProjects = Project::where('tenant_id', $tenantAndina->id)->count();

            $techProjects = $techExistingProjects < 7
                ? Project::factory()->count(7 - $techExistingProjects)->create(['tenant_id' => $tenantTech->id])
                : collect();

            $andinaProjects = $andinaExistingProjects < 6
                ? Project::factory()->count(6 - $andinaExistingProjects)->create(['tenant_id' => $tenantAndina->id])
                : collect();

            /*
            |--------------------------------------------------------------------------
            | TASKS FIJAS
            |--------------------------------------------------------------------------
            */

            $this->createTask([
                'tenant_id' => $tenantTech->id,
                'project_id' => $erpProject->id,
                'party_id' => $acme->id,
                'assigned_user_id' => $ownerTech->id,
                'name' => 'Reunión inicial con cliente',
                'description' => 'Primer relevamiento funcional.',
                'status' => 'pending',
                'due_date' => now()->addDays(2)->toDateString(),
            ]);

            $this->createTask([
                'tenant_id' => $tenantTech->id,
                'project_id' => $erpProject->id,
                'party_id' => $laura->id,
                'assigned_user_id' => $techUser->id,
                'name' => 'Relevar usuarios operativos',
                'description' => 'Entrevistas con usuarios clave.',
                'status' => 'in_progress',
                'due_date' => now()->addDays(5)->toDateString(),
            ]);

            $this->createTask([
                'tenant_id' => $tenantTech->id,
                'project_id' => null,
                'party_id' => $acme->id,
                'assigned_user_id' => $sharedUser->id,
                'name' => 'Actualizar condiciones comerciales',
                'description' => 'Seguimiento general del cliente.',
                'status' => 'pending',
                'due_date' => now()->addDays(8)->toDateString(),
            ]);

            $this->createTask([
                'tenant_id' => $tenantTech->id,
                'project_id' => $migrationProject->id,
                'party_id' => null,
                'assigned_user_id' => $ownerTech->id,
                'name' => 'Definir estructura de importación',
                'description' => 'Preparación técnica del módulo de migración.',
                'status' => 'done',
                'due_date' => now()->subDays(2)->toDateString(),
            ]);

            $this->createTask([
                'tenant_id' => $tenantAndina->id,
                'project_id' => $buildingProject->id,
                'party_id' => $obrasPatagonicas->id,
                'assigned_user_id' => $ownerAndina->id,
                'name' => 'Reunión con dirección de obra',
                'description' => 'Coordinación general del avance.',
                'status' => 'pending',
                'due_date' => now()->addDays(1)->toDateString(),
            ]);

            $this->createTask([
                'tenant_id' => $tenantAndina->id,
                'project_id' => $reviewProject->id,
                'party_id' => $marcos->id,
                'assigned_user_id' => $andinaUser->id,
                'name' => 'Revisar documentación estructural',
                'description' => 'Chequeo técnico previo.',
                'status' => 'in_progress',
                'due_date' => now()->addDays(4)->toDateString(),
            ]);

            $this->createTask([
                'tenant_id' => $tenantAndina->id,
                'project_id' => null,
                'party_id' => $obrasPatagonicas->id,
                'assigned_user_id' => $sharedUser->id,
                'name' => 'Coordinar ingreso de materiales',
                'description' => 'Seguimiento logístico.',
                'status' => 'pending',
                'due_date' => now()->addDays(6)->toDateString(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | TASKS AUTOMÁTICAS
            |--------------------------------------------------------------------------
            */

            $allTechParties = collect([$acme, $laura])->merge($techParties);
            $allTechProjects = collect([$erpProject, $migrationProject])->merge($techProjects);
            $techAssignableUsers = collect([$ownerTech, $sharedUser, $techUser]);

            for ($i = 0; $i < 20; $i++) {
                $project = fake()->boolean(70) ? $allTechProjects->random() : null;
                $party = fake()->boolean(75) ? $allTechParties->random() : null;
                $user = fake()->boolean(85) ? $techAssignableUsers->random() : null;

                $this->createTask([
                    'tenant_id' => $tenantTech->id,
                    'project_id' => $project?->id,
                    'party_id' => $party?->id,
                    'assigned_user_id' => $user?->id,
                    'name' => fake()->sentence(3),
                    'description' => fake()->optional()->paragraph(),
                    'status' => fake()->randomElement(['pending', 'in_progress', 'done', 'cancelled']),
                    'due_date' => fake()->boolean(80)
                        ? fake()->dateTimeBetween('-10 days', '+20 days')->format('Y-m-d')
                        : null,
                ]);
            }

            $allAndinaParties = collect([$obrasPatagonicas, $marcos])->merge($andinaParties);
            $allAndinaProjects = collect([$buildingProject, $reviewProject])->merge($andinaProjects);
            $andinaAssignableUsers = collect([$ownerAndina, $sharedUser, $andinaUser]);

            for ($i = 0; $i < 16; $i++) {
                $project = fake()->boolean(70) ? $allAndinaProjects->random() : null;
                $party = fake()->boolean(75) ? $allAndinaParties->random() : null;
                $user = fake()->boolean(85) ? $andinaAssignableUsers->random() : null;

                $this->createTask([
                    'tenant_id' => $tenantAndina->id,
                    'project_id' => $project?->id,
                    'party_id' => $party?->id,
                    'assigned_user_id' => $user?->id,
                    'name' => fake()->sentence(3),
                    'description' => fake()->optional()->paragraph(),
                    'status' => fake()->randomElement(['pending', 'in_progress', 'done', 'cancelled']),
                    'due_date' => fake()->boolean(80)
                        ? fake()->dateTimeBetween('-10 days', '+20 days')->format('Y-m-d')
                        : null,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | PRODUCTS
            |--------------------------------------------------------------------------
            */

            $techProducts = collect([
                $this->createProduct($tenantTech->id, 'Aceite 10W40', 'ACE-10W40', 'product', 'litro', 18500, true, 'Lubricante para service.'),
                $this->createProduct($tenantTech->id, 'Filtro de aceite', 'FILT-001', 'product', 'unidad', 8500, true, 'Repuesto estándar.'),
                $this->createProduct($tenantTech->id, 'Kit transmisión', 'KIT-TR-01', 'product', 'unidad', 69000, true, 'Kit completo.'),
                $this->createProduct($tenantTech->id, 'Service general', 'SERV-GRAL', 'service', 'servicio', 48000, true, 'Mano de obra completa.'),
                $this->createProduct($tenantTech->id, 'Diagnóstico', 'SERV-DIAG', 'service', 'servicio', 22000, true, 'Diagnóstico general.'),
            ]);

            $andinaProducts = collect([
                $this->createProduct($tenantAndina->id, 'Hormigón H21', 'H21-001', 'product', 'm3', 125000, true, 'Material de obra.'),
                $this->createProduct($tenantAndina->id, 'Hierro 8mm', 'HIER-8', 'product', 'barra', 18500, true, 'Hierro para estructura.'),
                $this->createProduct($tenantAndina->id, 'Servicio topográfico', 'SERV-TOPO', 'service', 'servicio', 150000, true, 'Relevamiento topográfico.'),
                $this->createProduct($tenantAndina->id, 'Inspección técnica', 'SERV-INSP', 'service', 'servicio', 98000, true, 'Inspección y control.'),
                $this->createProduct($tenantAndina->id, 'Movimiento de suelo', 'SERV-SUELO', 'service', 'jornada', 210000, true, 'Trabajo con maquinaria.'),
            ]);

            /*
            |--------------------------------------------------------------------------
            | DOCUMENT SEQUENCES
            |--------------------------------------------------------------------------
            */

            $this->createDocumentSequences($tenantTech->id, $techBranches);
            $this->createDocumentSequences($tenantAndina->id, $andinaBranches);

            /*
            |--------------------------------------------------------------------------
            | ORDERS + ITEMS
            |--------------------------------------------------------------------------
            */

            $techOrders = collect([
                $this->createOrderWithItems(
                    tenantId: $tenantTech->id,
                    partyId: $acme->id,
                    createdBy: $ownerTech->id,
                    updatedBy: $ownerTech->id,
                    kind: 'sale',
                    number: 'TECH-ORD-0001',
                    status: 'draft',
                    orderedAt: now()->subDays(5)->toDateString(),
                    notes: 'Pedido inicial de cliente estratégico.',
                    items: [
                        ['product' => $techProducts[0], 'description' => 'Aceite 10W40', 'quantity' => 2, 'unit_price' => 18500],
                        ['product' => $techProducts[1], 'description' => 'Filtro de aceite', 'quantity' => 1, 'unit_price' => 8500],
                        ['product' => $techProducts[3], 'kind' => 'service', 'description' => 'Service general', 'quantity' => 1, 'unit_price' => 48000],
                    ]
                ),
                $this->createOrderWithItems(
                    tenantId: $tenantTech->id,
                    partyId: $laura->id,
                    createdBy: $techUser->id,
                    updatedBy: $techUser->id,
                    kind: 'service',
                    number: 'TECH-ORD-0002',
                    status: 'confirmed',
                    orderedAt: now()->subDays(2)->toDateString(),
                    notes: 'Trabajo técnico confirmado.',
                    items: [
                        ['product' => $techProducts[2], 'description' => 'Kit transmisión', 'quantity' => 1, 'unit_price' => 69000],
                        ['product' => $techProducts[4], 'description' => 'Diagnóstico', 'quantity' => 1, 'unit_price' => 22000],
                    ]
                ),
                $this->createOrderWithItems(
                    tenantId: $tenantTech->id,
                    partyId: $acme->id,
                    createdBy: $sharedUser->id,
                    updatedBy: $sharedUser->id,
                    kind: 'purchase',
                    number: 'TECH-ORD-0003',
                    status: 'cancelled',
                    orderedAt: now()->subDays(1)->toDateString(),
                    notes: 'Compra demo cancelada para probar estados.',
                    items: [
                        ['product' => $techProducts[1], 'description' => 'Filtro de aceite', 'quantity' => 4, 'unit_price' => 8500],
                        ['product' => $techProducts[2], 'description' => 'Kit transmisión', 'quantity' => 1, 'unit_price' => 69000],
                    ]
                ),
            ]);

            $andinaOrders = collect([
                $this->createOrderWithItems(
                    tenantId: $tenantAndina->id,
                    partyId: $obrasPatagonicas->id,
                    createdBy: $ownerAndina->id,
                    updatedBy: $ownerAndina->id,
                    kind: 'sale',
                    number: 'AND-ORD-0001',
                    status: 'draft',
                    orderedAt: now()->subDays(6)->toDateString(),
                    notes: 'Materiales para avance de obra.',
                    items: [
                        ['product' => $andinaProducts[0], 'description' => 'Hormigón H21', 'quantity' => 8, 'unit_price' => 125000],
                        ['product' => $andinaProducts[1], 'description' => 'Hierro 8mm', 'quantity' => 30, 'unit_price' => 18500],
                    ]
                ),
                $this->createOrderWithItems(
                    tenantId: $tenantAndina->id,
                    partyId: $marcos->id,
                    createdBy: $andinaUser->id,
                    updatedBy: $andinaUser->id,
                    kind: 'service',
                    number: 'AND-ORD-0002',
                    status: 'confirmed',
                    orderedAt: now()->subDay()->toDateString(),
                    notes: 'Servicios técnicos programados.',
                    items: [
                        ['product' => $andinaProducts[2], 'description' => 'Servicio topográfico', 'quantity' => 1, 'unit_price' => 150000],
                        ['product' => $andinaProducts[3], 'description' => 'Inspección técnica', 'quantity' => 1, 'unit_price' => 98000],
                    ]
                ),
                $this->createOrderWithItems(
                    tenantId: $tenantAndina->id,
                    partyId: $obrasPatagonicas->id,
                    createdBy: $sharedUser->id,
                    updatedBy: $sharedUser->id,
                    kind: 'purchase',
                    number: 'AND-ORD-0003',
                    status: 'cancelled',
                    orderedAt: now()->subDays(3)->toDateString(),
                    notes: 'Compra demo cancelada.',
                    items: [
                        ['product' => $andinaProducts[1], 'description' => 'Hierro 8mm', 'quantity' => 10, 'unit_price' => 18500],
                    ]
                ),
            ]);

            /*
            |--------------------------------------------------------------------------
            | DOCUMENTS + ITEMS
            |--------------------------------------------------------------------------
            */

            $this->createDocumentWithItems(
                tenantId: $tenantTech->id,
                partyId: $acme->id,
                orderId: $techOrders[0]->id,
                createdBy: $ownerTech->id,
                updatedBy: $ownerTech->id,
                kind: 'quote',
                number: 'PRE-00000001',
                status: 'draft',
                issuedAt: now()->subDays(5)->toDateString(),
                dueAt: now()->addDays(10)->toDateString(),
                currencyCode: 'ARS',
                notes: 'Presupuesto asociado a la orden.',
                items: [
                    ['product' => $techProducts[0], 'description' => 'Aceite 10W40', 'kind' => 'product', 'quantity' => 2, 'unit_price' => 18500],
                    ['product' => $techProducts[3], 'kind' => 'service', 'description' => 'Service general', 'quantity' => 1, 'unit_price' => 48000],
                ]
            );

            $this->createDocumentWithItems(
                tenantId: $tenantTech->id,
                partyId: $laura->id,
                orderId: $techOrders[1]->id,
                createdBy: $techUser->id,
                updatedBy: $techUser->id,
                kind: 'work_order',
                number: 'OTR-00000001',
                status: 'draft',
                issuedAt: now()->subDays(1)->toDateString(),
                dueAt: now()->addDays(3)->toDateString(),
                currencyCode: 'ARS',
                notes: 'Orden de trabajo generada desde pedido confirmado.',
                items: [
                    ['product' => $techProducts[2], 'description' => 'Kit transmisión', 'kind' => 'product', 'quantity' => 1, 'unit_price' => 69000],
                    ['product' => $techProducts[4], 'description' => 'Diagnóstico', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 22000],
                ]
            );

            $this->createDocumentWithItems(
                tenantId: $tenantTech->id,
                partyId: $acme->id,
                orderId: null,
                createdBy: $sharedUser->id,
                updatedBy: $sharedUser->id,
                kind: 'receipt',
                number: 'REC-00000001',
                status: 'issued',
                issuedAt: now()->toDateString(),
                dueAt: null,
                currencyCode: 'ARS',
                notes: 'Recibo demo independiente.',
                items: [
                    ['product' => $techProducts[1], 'description' => 'Filtro de aceite', 'kind' => 'product', 'quantity' => 2, 'unit_price' => 8500],
                ]
            );

            $this->createDocumentWithItems(
                tenantId: $tenantAndina->id,
                partyId: $obrasPatagonicas->id,
                orderId: $andinaOrders[0]->id,
                createdBy: $ownerAndina->id,
                updatedBy: $ownerAndina->id,
                kind: 'invoice',
                number: 'FAC-00000001',
                status: 'draft',
                issuedAt: now()->subDays(4)->toDateString(),
                dueAt: now()->addDays(15)->toDateString(),
                currencyCode: 'ARS',
                notes: 'Factura demo con materiales.',
                items: [
                    ['product' => $andinaProducts[0], 'description' => 'Hormigón H21', 'kind' => 'product', 'quantity' => 8, 'unit_price' => 125000],
                    ['product' => $andinaProducts[1], 'description' => 'Hierro 8mm', 'kind' => 'product', 'quantity' => 30, 'unit_price' => 18500],
                ]
            );

            $this->createDocumentWithItems(
                tenantId: $tenantAndina->id,
                partyId: $marcos->id,
                orderId: $andinaOrders[1]->id,
                createdBy: $andinaUser->id,
                updatedBy: $andinaUser->id,
                kind: 'receipt',
                number: 'REC-00000002',
                status: 'issued',
                issuedAt: now()->toDateString(),
                dueAt: null,
                currencyCode: 'ARS',
                notes: 'Recibo demo por servicios.',
                items: [
                    ['product' => $andinaProducts[2], 'description' => 'Servicio topográfico', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 150000],
                    ['product' => $andinaProducts[3], 'description' => 'Inspección técnica', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 98000],
                ]
            );
        });
    }

    protected function createMembership(Tenant $tenant, User $user, bool $isOwner): void
    {
        Membership::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'active',
                'is_owner' => $isOwner,
                'joined_at' => now(),
            ]
        );
    }

    protected function createRole(Tenant $tenant, string $name, string $slug, ?string $description = null): Role
    {
        return Role::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'description' => $description,
            ]
        );
    }

    protected function createInvitation(Tenant $tenant, string $email): Invitation
    {
        return Invitation::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => $email,
            ],
            [
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
                'accepted_ip' => null,
                'user_agent' => 'DemoSeeder',
                'meta' => ['source' => 'demo-seeder'],
            ]
        );
    }

    protected function createTask(array $data): Task
    {
        $query = Task::where('tenant_id', $data['tenant_id'])
            ->where('name', $data['name']);

        if (array_key_exists('project_id', $data)) {
            if ($data['project_id'] === null) {
                $query->whereNull('project_id');
            } else {
                $query->where('project_id', $data['project_id']);
            }
        }

        if (array_key_exists('party_id', $data)) {
            if ($data['party_id'] === null) {
                $query->whereNull('party_id');
            } else {
                $query->where('party_id', $data['party_id']);
            }
        }

        $existing = $query->first();

        if ($existing) {
            return $existing;
        }

        return Task::create($data);
    }

    protected function createProduct(
        string $tenantId,
        string $name,
        ?string $sku,
        string $kind,
        string $unitLabel,
        float $price,
        bool $isActive = true,
        ?string $description = null
    ): Product {
        return Product::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => $name,
            ],
            [
                'sku' => $sku,
                'description' => $description,
                'price' => $price,
                'kind' => $kind,
                'unit_label' => $unitLabel,
                'is_active' => $isActive,
            ]
        );
    }

    protected function createDocumentSequences(string $tenantId, Collection $branches): void
    {
        $definitions = [
            ['doc_type' => 'quote', 'prefix' => 'PRE'],
            ['doc_type' => 'invoice', 'prefix' => 'FAC'],
            ['doc_type' => 'work_order', 'prefix' => 'OTR'],
            ['doc_type' => 'receipt', 'prefix' => 'REC'],
        ];

        foreach ($branches as $branch) {
            foreach ($definitions as $definition) {
                DB::table('document_sequences')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'branch_id' => $branch->id,
                        'doc_type' => $definition['doc_type'],
                    ],
                    [
                        'prefix' => $definition['prefix'],
                        'padding' => 8,
                        'next_number' => 2,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    protected function createOrderWithItems(
        string $tenantId,
        ?int $partyId,
        ?int $createdBy,
        ?int $updatedBy,
        string $kind,
        ?string $number,
        string $status,
        ?string $orderedAt,
        ?string $notes,
        array $items
    ): Order {
        $order = Order::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'number' => $number,
            ],
            [
                'party_id' => $partyId,
                'kind' => $kind,
                'status' => $status,
                'ordered_at' => $orderedAt,
                'notes' => $notes,
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
            ]
        );

        if (!DB::table('order_items')->where('tenant_id', $tenantId)->where('order_id', $order->id)->exists()) {
            foreach ($items as $index => $item) {
                DB::table('order_items')->insert([
                    'tenant_id' => $tenantId,
                    'order_id' => $order->id,
                    'product_id' => $item['product']?->id,
                    'position' => $index + 1,
                    'kind' => $item['kind'] ?? ($item['product']?->kind ?? 'product'),
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);
            }
        }

        return $order->fresh();
    }

    protected function createDocumentWithItems(
        string $tenantId,
        ?int $partyId,
        ?int $orderId,
        ?int $createdBy,
        ?int $updatedBy,
        string $kind,
        ?string $number,
        string $status,
        ?string $issuedAt,
        ?string $dueAt,
        ?string $currencyCode,
        ?string $notes,
        array $items
    ): void {
        $subtotal = 0;

        $normalizedItems = collect($items)->map(function ($item, $index) use (&$subtotal) {
            $lineTotal = round(((float) $item['quantity']) * ((float) $item['unit_price']), 2);
            $subtotal += $lineTotal;

            return [
                'product_id' => $item['product']?->id,
                'position' => $index + 1,
                'kind' => $item['kind'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => $lineTotal,
            ];
        });

        $taxTotal = 0;
        $total = $subtotal + $taxTotal;

        $document = DB::table('documents')
            ->where('tenant_id', $tenantId)
            ->where('number', $number)
            ->first();

        if (!$document) {
            $documentId = DB::table('documents')->insertGetId([
                'tenant_id' => $tenantId,
                'party_id' => $partyId,
                'order_id' => $orderId,
                'kind' => $kind,
                'number' => $number,
                'status' => $status,
                'issued_at' => $issuedAt,
                'due_at' => $dueAt,
                'currency_code' => $currencyCode,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'notes' => $notes,
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        } else {
            $documentId = $document->id;
        }

        if (!DB::table('document_items')->where('tenant_id', $tenantId)->where('document_id', $documentId)->exists()) {
            foreach ($normalizedItems as $item) {
                DB::table('document_items')->insert([
                    'tenant_id' => $tenantId,
                    'document_id' => $documentId,
                    'product_id' => $item['product_id'],
                    'position' => $item['position'],
                    'kind' => $item['kind'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);
            }
        }
    }
}
