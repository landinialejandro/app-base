<?php

// FILE: database/seeders/Modules/ProjectModuleSeeder.php | V2

namespace Database\Seeders\Modules;

use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants')) {
            throw new \RuntimeException('ProjectModuleSeeder requires tenants');
        }

        $tenants = $this->getDependency('tenants');
        $projects = [];

        $projects['tech'] = $this->createProjectsForTenant(
            tenant: $tenants['tech'],
            fixedProjects: [
                ['name' => 'Implementación ERP', 'description' => 'Implementación inicial del sistema.'],
                ['name' => 'Migración Base de Datos', 'description' => 'Migración del sistema legado.'],
            ],
            targetCount: (int) config('seeders.demo.tech.target_projects', 7)
        );

        $projects['andina'] = $this->createProjectsForTenant(
            tenant: $tenants['andina'],
            fixedProjects: [
                ['name' => 'Edificio Central', 'description' => 'Proyecto principal de obra.'],
                ['name' => 'Revisión Estructural', 'description' => 'Validación técnica documental.'],
            ],
            targetCount: (int) config('seeders.demo.andina.target_projects', 6)
        );

        $this->context['projects'] = $projects;
    }

    private function createProjectsForTenant($tenant, array $fixedProjects, int $targetCount): Collection
    {
        $createdFixed = collect();

        foreach ($fixedProjects as $definition) {
            $createdFixed->push($this->createProject(
                tenant: $tenant,
                name: $definition['name'],
                description: $definition['description'] ?? null
            ));
        }

        $existingCount = Project::query()
            ->where('tenant_id', $tenant->id)
            ->count();

        $neededCount = max(0, $targetCount - $existingCount);

        $extra = $neededCount > 0
            ? Project::factory()->count($neededCount)->create(['tenant_id' => $tenant->id])
            : collect();

        return $createdFixed->merge($extra);
    }

    private function createProject($tenant, string $name, ?string $description = null): Project
    {
        return Project::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => $name,
            ],
            [
                'description' => $description,
                'status' => 'active',
            ]
        );
    }
}
