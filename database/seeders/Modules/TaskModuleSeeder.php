<?php

// FILE: database/seeders/Modules/TaskModuleSeeder.php | V2

namespace Database\Seeders\Modules;

use App\Models\Task;
use Illuminate\Support\Collection;

class TaskModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (
            ! $this->hasDependency('tenants')
            || ! $this->hasDependency('users')
            || ! $this->hasDependency('parties')
            || ! $this->hasDependency('projects')
        ) {
            throw new \RuntimeException('TaskModuleSeeder requires tenants, users, parties, and projects');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $parties = $this->getDependency('parties');
        $projects = $this->getDependency('projects');

        $tasks = [];

        $tasks['tech'] = $this->createTechTasks(
            $tenants['tech'],
            $users,
            $parties['techFixed']->merge($parties['techExtra']),
            $projects['tech']
        );

        $tasks['andina'] = $this->createAndinaTasks(
            $tenants['andina'],
            $users,
            $parties['andinaFixed']->merge($parties['andinaExtra']),
            $projects['andina']
        );

        $this->context['tasks'] = $tasks;
    }

    private function createTechTasks($tenant, array $users, Collection $parties, Collection $projects): Collection
    {
        $tasks = collect();

        $erpProject = $projects->firstWhere('name', 'Implementación ERP');
        $migrationProject = $projects->firstWhere('name', 'Migración Base de Datos');

        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $erpProject?->id,
            'party_id' => $parties->first()?->id,
            'assigned_user_id' => $users['ownerTech']->id,
            'name' => 'Reunión inicial con cliente',
            'description' => 'Primer relevamiento funcional.',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => now()->addDays(2)->toDateString(),
        ]));

        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $erpProject?->id,
            'party_id' => $parties->skip(1)->first()?->id,
            'assigned_user_id' => $users['techUser']->id,
            'name' => 'Relevar usuarios operativos',
            'description' => 'Entrevistas con usuarios clave.',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'due_date' => now()->addDays(5)->toDateString(),
        ]));

        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => null,
            'party_id' => $parties->first()?->id,
            'assigned_user_id' => $users['shared']->id,
            'name' => 'Actualizar condiciones comerciales',
            'description' => 'Seguimiento general del cliente.',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => now()->addDays(8)->toDateString(),
        ]));

        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $migrationProject?->id,
            'party_id' => null,
            'assigned_user_id' => $users['ownerTech']->id,
            'name' => 'Definir estructura de importación',
            'description' => 'Preparación técnica del módulo de migración.',
            'status' => 'done',
            'priority' => 'medium',
            'due_date' => now()->subDays(2)->toDateString(),
        ]));

        return $tasks->merge(
            $this->generateDeterministicTasks(
                tenantId: $tenant->id,
                assignableUsers: collect([
                    $users['ownerTech'],
                    $users['shared'],
                    $users['techUser'],
                ]),
                parties: $parties,
                projects: $projects,
                count: (int) config('seeders.demo.tech.target_tasks', 20),
                fixedCount: $tasks->count(),
                seedPrefix: 'TECH'
            )
        );
    }

    private function createAndinaTasks($tenant, array $users, Collection $parties, Collection $projects): Collection
    {
        $tasks = collect();

        $buildingProject = $projects->firstWhere('name', 'Edificio Central');
        $reviewProject = $projects->firstWhere('name', 'Revisión Estructural');

        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $buildingProject?->id,
            'party_id' => $parties->first()?->id,
            'assigned_user_id' => $users['ownerAndina']->id,
            'name' => 'Reunión con dirección de obra',
            'description' => 'Coordinación general del avance.',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => now()->addDays(1)->toDateString(),
        ]));

        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => $reviewProject?->id,
            'party_id' => $parties->skip(1)->first()?->id,
            'assigned_user_id' => $users['andinaUser']->id,
            'name' => 'Revisar documentación estructural',
            'description' => 'Chequeo técnico previo.',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'due_date' => now()->addDays(4)->toDateString(),
        ]));

        $tasks->push($this->createTask([
            'tenant_id' => $tenant->id,
            'project_id' => null,
            'party_id' => $parties->first()?->id,
            'assigned_user_id' => $users['shared']->id,
            'name' => 'Coordinar ingreso de materiales',
            'description' => 'Seguimiento logístico.',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => now()->addDays(6)->toDateString(),
        ]));

        return $tasks->merge(
            $this->generateDeterministicTasks(
                tenantId: $tenant->id,
                assignableUsers: collect([
                    $users['ownerAndina'],
                    $users['shared'],
                    $users['andinaUser'],
                ]),
                parties: $parties,
                projects: $projects,
                count: (int) config('seeders.demo.andina.target_tasks', 16),
                fixedCount: $tasks->count(),
                seedPrefix: 'AND'
            )
        );
    }

    private function generateDeterministicTasks(
        string $tenantId,
        Collection $assignableUsers,
        Collection $parties,
        Collection $projects,
        int $count,
        int $fixedCount,
        string $seedPrefix
    ): Collection {
        $tasks = collect();
        $remaining = max(0, $count - $fixedCount);

        for ($i = 1; $i <= $remaining; $i++) {
            $user = $assignableUsers[($i - 1) % max(1, $assignableUsers->count())];
            $project = $projects->isNotEmpty() && $i % 4 !== 0
                ? $projects[($i - 1) % $projects->count()]
                : null;
            $party = $parties->isNotEmpty() && $i % 5 !== 0
                ? $parties[($i - 1) % $parties->count()]
                : null;

            $tasks->push($this->createTask([
                'tenant_id' => $tenantId,
                'project_id' => $project?->id,
                'party_id' => $party?->id,
                'assigned_user_id' => $user->id,
                'name' => sprintf('%s tarea %02d', $seedPrefix, $i),
                'description' => sprintf('Tarea demo %02d del tenant %s.', $i, $seedPrefix),
                'status' => $this->statusForIndex($i),
                'priority' => $this->priorityForIndex($i),
                'due_date' => now()->addDays(($i % 12) + 1)->toDateString(),
            ]));
        }

        return $tasks;
    }

    private function statusForIndex(int $index): string
    {
        $statuses = ['pending', 'in_progress', 'done', 'cancelled'];

        return $statuses[($index - 1) % count($statuses)];
    }

    private function priorityForIndex(int $index): string
    {
        $priorities = ['low', 'medium', 'high', 'urgent'];

        return $priorities[($index - 1) % count($priorities)];
    }

    private function createTask(array $data): Task
    {
        $query = Task::query()
            ->where('tenant_id', $data['tenant_id'])
            ->where('name', $data['name']);

        if (($data['project_id'] ?? null) === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $data['project_id']);
        }

        if (($data['party_id'] ?? null) === null) {
            $query->whereNull('party_id');
        } else {
            $query->where('party_id', $data['party_id']);
        }

        $existing = $query->first();

        if ($existing) {
            $existing->update([
                'assigned_user_id' => $data['assigned_user_id'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'priority' => $data['priority'] ?? 'medium',
                'due_date' => $data['due_date'] ?? null,
            ]);

            return $existing;
        }

        return Task::create($data);
    }
}
