<?php

// database/seeders/Modules/TaskModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Task;
use Illuminate\Support\Collection;

class TaskModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants') || ! $this->hasDependency('users') || ! $this->hasDependency('parties') || ! $this->hasDependency('projects')) {
            throw new \RuntimeException('TaskModuleSeeder requires tenants, users, parties, and projects');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $parties = $this->getDependency('parties');
        $projects = $this->getDependency('projects');
        $tasks = [];

        // Create fixed tasks for Tech
        $techFixedTasks = $this->createTechFixedTasks(
            $tenants['tech'],
            $users,
            $parties['techFixed'],
            $projects['tech']
        );

        // Create fixed tasks for Andina
        $andinaFixedTasks = $this->createAndinaFixedTasks(
            $tenants['andina'],
            $users,
            $parties['andinaFixed'],
            $projects['andina']
        );

        // Generate random tasks
        $techRandomTasks = $this->generateRandomTasks(
            $tenants['tech'],
            $users,
            $parties['techFixed']->merge($parties['techExtra']),
            $projects['tech'],
            20
        );

        $andinaRandomTasks = $this->generateRandomTasks(
            $tenants['andina'],
            $users,
            $parties['andinaFixed']->merge($parties['andinaExtra']),
            $projects['andina'],
            16
        );

        $tasks['tech'] = $techFixedTasks->merge($techRandomTasks);
        $tasks['andina'] = $andinaFixedTasks->merge($andinaRandomTasks);

        $this->context['tasks'] = $tasks;
    }

    private function createTechFixedTasks($tenant, array $users, $parties, $projects): Collection
    {
        $tasks = collect();
        $acme = $parties[0] ?? null;
        $laura = $parties[1] ?? null;
        $erpProject = $projects->firstWhere('name', 'Implementación ERP');
        $migrationProject = $projects->firstWhere('name', 'Migración Base de Datos');

        // Reunión inicial con cliente
        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $erpProject?->id,
            'party_id' => $acme?->id,
            'assigned_user_id' => $users['ownerTech']->id,
            'name' => 'Reunión inicial con cliente',
            'description' => 'Primer relevamiento funcional.',
            'status' => 'pending',
            'due_date' => now()->addDays(2)->toDateString(),
        ]));

        // Relevar usuarios operativos
        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $erpProject?->id,
            'party_id' => $laura?->id,
            'assigned_user_id' => $users['techUser']->id,
            'name' => 'Relevar usuarios operativos',
            'description' => 'Entrevistas con usuarios clave.',
            'status' => 'in_progress',
            'due_date' => now()->addDays(5)->toDateString(),
        ]));

        // Actualizar condiciones comerciales
        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => null,
            'party_id' => $acme?->id,
            'assigned_user_id' => $users['shared']->id,
            'name' => 'Actualizar condiciones comerciales',
            'description' => 'Seguimiento general del cliente.',
            'status' => 'pending',
            'due_date' => now()->addDays(8)->toDateString(),
        ]));

        // Definir estructura de importación
        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $migrationProject?->id,
            'party_id' => null,
            'assigned_user_id' => $users['ownerTech']->id,
            'name' => 'Definir estructura de importación',
            'description' => 'Preparación técnica del módulo de migración.',
            'status' => 'done',
            'due_date' => now()->subDays(2)->toDateString(),
        ]));

        return $tasks;
    }

    private function createAndinaFixedTasks($tenant, array $users, $parties, $projects): Collection
    {
        $tasks = collect();
        $obrasPatagonicas = $parties[0] ?? null;
        $marcos = $parties[1] ?? null;
        $buildingProject = $projects->firstWhere('name', 'Edificio Central');
        $reviewProject = $projects->firstWhere('name', 'Revisión Estructural');

        // Reunión con dirección de obra
        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $buildingProject?->id,
            'party_id' => $obrasPatagonicas?->id,
            'assigned_user_id' => $users['ownerAndina']->id,
            'name' => 'Reunión con dirección de obra',
            'description' => 'Coordinación general del avance.',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->toDateString(),
        ]));

        // Revisar documentación estructural
        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $reviewProject?->id,
            'party_id' => $marcos?->id,
            'assigned_user_id' => $users['andinaUser']->id,
            'name' => 'Revisar documentación estructural',
            'description' => 'Chequeo técnico previo.',
            'status' => 'in_progress',
            'due_date' => now()->addDays(4)->toDateString(),
        ]));

        // Coordinar ingreso de materiales
        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => null,
            'party_id' => $obrasPatagonicas?->id,
            'assigned_user_id' => $users['shared']->id,
            'name' => 'Coordinar ingreso de materiales',
            'description' => 'Seguimiento logístico.',
            'status' => 'pending',
            'due_date' => now()->addDays(6)->toDateString(),
        ]));

        return $tasks;
    }

    private function generateRandomTasks($tenant, array $users, Collection $parties, Collection $projects, int $count): Collection
    {
        $tasks = collect();
        $assignableUsers = collect([$users['ownerTech'] ?? $users['ownerAndina'], $users['shared'], $users['techUser'] ?? $users['andinaUser']]);

        for ($i = 0; $i < $count; $i++) {
            $project = fake()->boolean(70) ? $projects->random() : null;
            $party = fake()->boolean(75) ? $parties->random() : null;
            $user = fake()->boolean(85) ? $assignableUsers->random() : null;

            $existing = Task::where('tenant_id', $tenant->id)
                ->where('name', fake()->sentence(3))
                ->where('project_id', $project?->id)
                ->where('party_id', $party?->id)
                ->first();

            if (! $existing) {
                $tasks->push(Task::create([
                    'tenant_id' => $tenant->id,
                    'project_id' => $project?->id,
                    'party_id' => $party?->id,
                    'assigned_user_id' => $user?->id,
                    'name' => fake()->sentence(3),
                    'description' => fake()->optional()->paragraph(),
                    'status' => fake()->randomElement(['pending', 'in_progress', 'done', 'cancelled']),
                    'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
                    'due_date' => fake()->boolean(80)
                        ? fake()->dateTimeBetween('-10 days', '+20 days')->format('Y-m-d')
                        : null,
                ]));
            }
        }

        return $tasks;
    }

    private function createTask(array $data): Task
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
}
