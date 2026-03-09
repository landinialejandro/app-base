<?php

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | TENANTS FIJOS
        |--------------------------------------------------------------------------
        */

        $tenantTech = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Tech Solutions SA',
            'slug' => 'tech-solutions-sa',
            'settings' => [
                'timezone' => 'America/Argentina/Salta',
                'currency' => 'ARS',
            ],
        ]);

        $tenantAndina = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Constructora Andina SRL',
            'slug' => 'constructora-andina-srl',
            'settings' => [
                'timezone' => 'America/Argentina/Salta',
                'currency' => 'ARS',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | USERS FIJOS
        |--------------------------------------------------------------------------
        | password para todos: password
        |--------------------------------------------------------------------------
        */

        $ownerTech = User::create([
            'name' => 'Juan Tech',
            'email' => 'juan@tech.local',
            'password' => 'password',
        ]);

        $ownerAndina = User::create([
            'name' => 'María Andina',
            'email' => 'maria@andina.local',
            'password' => 'password',
        ]);

        $sharedUser = User::create([
            'name' => 'Carlos Operaciones',
            'email' => 'carlos@demo.local',
            'password' => 'password',
        ]);

        $techUser = User::create([
            'name' => 'Ana Comercial',
            'email' => 'ana@demo.local',
            'password' => 'password',
        ]);

        $andinaUser = User::create([
            'name' => 'Pedro Obra',
            'email' => 'pedro@demo.local',
            'password' => 'password',
        ]);

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
        | PARTIES - DATOS FIJOS
        |--------------------------------------------------------------------------
        */

        $acme = Party::create([
            'tenant_id' => $tenantTech->id,
            'kind' => 'company',
            'name' => 'Empresa ACME',
            'display_name' => 'ACME',
            'document_type' => 'CUIT',
            'document_number' => '30-12345678-9',
            'tax_id' => '30-12345678-9',
            'email' => 'contacto@acme.local',
            'phone' => '299-555-1001',
            'address' => 'Neuquén Capital',
            'notes' => 'Cliente estratégico.',
            'is_active' => true,
        ]);

        $laura = Party::create([
            'tenant_id' => $tenantTech->id,
            'kind' => 'person',
            'name' => 'Laura Fernández',
            'display_name' => 'Laura Fernández',
            'document_type' => 'DNI',
            'document_number' => '27123456',
            'tax_id' => null,
            'email' => 'laura@cliente.local',
            'phone' => '299-555-1004',
            'address' => 'Centenario, Neuquén',
            'notes' => 'Contacto operativo.',
            'is_active' => true,
        ]);

        $obrasPatagonicas = Party::create([
            'tenant_id' => $tenantAndina->id,
            'kind' => 'company',
            'name' => 'Obras Patagónicas',
            'display_name' => 'Obras Patagónicas',
            'document_type' => 'CUIT',
            'document_number' => '30-42345678-3',
            'tax_id' => '30-42345678-3',
            'email' => 'info@obraspat.local',
            'phone' => '299-555-2001',
            'address' => 'Neuquén Capital',
            'notes' => 'Cliente de obras privadas.',
            'is_active' => true,
        ]);

        $marcos = Party::create([
            'tenant_id' => $tenantAndina->id,
            'kind' => 'person',
            'name' => 'Marcos Quiroga',
            'display_name' => 'Marcos Quiroga',
            'document_type' => 'DNI',
            'document_number' => '30111222',
            'tax_id' => null,
            'email' => 'marcos@obra.local',
            'phone' => '299-555-2003',
            'address' => 'Añelo, Neuquén',
            'notes' => 'Supervisor externo.',
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | PARTIES - VOLUMEN AUTOMÁTICO
        |--------------------------------------------------------------------------
        */

        $techParties = Party::factory()->count(10)->create([
            'tenant_id' => $tenantTech->id,
        ]);

        $andinaParties = Party::factory()->count(8)->create([
            'tenant_id' => $tenantAndina->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | PROJECTS FIJOS
        |--------------------------------------------------------------------------
        */

        $erpProject = Project::create([
            'tenant_id' => $tenantTech->id,
            'name' => 'Implementación ERP',
            'description' => 'Implementación inicial del sistema.',
        ]);

        $migrationProject = Project::create([
            'tenant_id' => $tenantTech->id,
            'name' => 'Migración Base de Datos',
            'description' => 'Migración del sistema legado.',
        ]);

        $buildingProject = Project::create([
            'tenant_id' => $tenantAndina->id,
            'name' => 'Edificio Central',
            'description' => 'Proyecto principal de obra.',
        ]);

        $reviewProject = Project::create([
            'tenant_id' => $tenantAndina->id,
            'name' => 'Revisión Estructural',
            'description' => 'Validación técnica documental.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | PROJECTS - VOLUMEN AUTOMÁTICO
        |--------------------------------------------------------------------------
        */

        $techProjects = Project::factory()->count(5)->create([
            'tenant_id' => $tenantTech->id,
        ]);

        $andinaProjects = Project::factory()->count(4)->create([
            'tenant_id' => $tenantAndina->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | TASKS FIJAS
        |--------------------------------------------------------------------------
        */

        Task::create([
            'tenant_id' => $tenantTech->id,
            'project_id' => $erpProject->id,
            'party_id' => $acme->id,
            'assigned_user_id' => $ownerTech->id,
            'name' => 'Reunión inicial con cliente',
            'description' => 'Primer relevamiento funcional.',
            'status' => 'pending',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Task::create([
            'tenant_id' => $tenantTech->id,
            'project_id' => $erpProject->id,
            'party_id' => $laura->id,
            'assigned_user_id' => $techUser->id,
            'name' => 'Relevar usuarios operativos',
            'description' => 'Entrevistas con usuarios clave.',
            'status' => 'in_progress',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        Task::create([
            'tenant_id' => $tenantAndina->id,
            'project_id' => $buildingProject->id,
            'party_id' => $obrasPatagonicas->id,
            'assigned_user_id' => $ownerAndina->id,
            'name' => 'Reunión con dirección de obra',
            'description' => 'Coordinación general del avance.',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->toDateString(),
        ]);

        Task::create([
            'tenant_id' => $tenantAndina->id,
            'project_id' => $reviewProject->id,
            'party_id' => $marcos->id,
            'assigned_user_id' => $andinaUser->id,
            'name' => 'Revisar documentación estructural',
            'description' => 'Chequeo técnico previo.',
            'status' => 'in_progress',
            'due_date' => now()->addDays(4)->toDateString(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | TASKS AUTOMÁTICAS - TENANT TECH
        |--------------------------------------------------------------------------
        */

        $allTechParties = $techParties->push($acme)->push($laura);
        $allTechProjects = $techProjects->push($erpProject)->push($migrationProject);
        $techAssignableUsers = collect([$ownerTech, $sharedUser, $techUser]);

        for ($i = 0; $i < 20; $i++) {
            $project = fake()->boolean(75) ? $allTechProjects->random() : null;
            $party = fake()->boolean(80) ? $allTechParties->random() : null;
            $user = fake()->boolean(90) ? $techAssignableUsers->random() : null;

            Task::create([
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

        /*
        |--------------------------------------------------------------------------
        | TASKS AUTOMÁTICAS - TENANT ANDINA
        |--------------------------------------------------------------------------
        */

        $allAndinaParties = $andinaParties->push($obrasPatagonicas)->push($marcos);
        $allAndinaProjects = $andinaProjects->push($buildingProject)->push($reviewProject);
        $andinaAssignableUsers = collect([$ownerAndina, $sharedUser, $andinaUser]);

        for ($i = 0; $i < 16; $i++) {
            $project = fake()->boolean(75) ? $allAndinaProjects->random() : null;
            $party = fake()->boolean(80) ? $allAndinaParties->random() : null;
            $user = fake()->boolean(90) ? $andinaAssignableUsers->random() : null;

            Task::create([
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
    }

    protected function createMembership(Tenant $tenant, User $user, bool $isOwner): void
    {
        Membership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'active',
            'is_owner' => $isOwner,
            'joined_at' => now(),
        ]);
    }
}