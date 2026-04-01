<?php

// database/seeders/Modules/ProjectModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Project;

class ProjectModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants')) {
            throw new \RuntimeException('ProjectModuleSeeder requires tenants');
        }

        $tenants = $this->getDependency('tenants');
        $projects = [];

        // Tech fixed projects
        $techProjects = collect([
            $this->createProject($tenants['tech'], 'Implementación ERP', 'Implementación inicial del sistema.'),
            $this->createProject($tenants['tech'], 'Migración Base de Datos', 'Migración del sistema legado.'),
        ]);

        // Andina fixed projects
        $andinaProjects = collect([
            $this->createProject($tenants['andina'], 'Edificio Central', 'Proyecto principal de obra.'),
            $this->createProject($tenants['andina'], 'Revisión Estructural', 'Validación técnica documental.'),
        ]);

        // Generate additional projects if needed
        $techExistingCount = Project::where('tenant_id', $tenants['tech']->id)->count();
        $andinaExistingCount = Project::where('tenant_id', $tenants['andina']->id)->count();

        $techTarget = config('seeders.demo.tech.target_projects', 7);
        $andinaTarget = config('seeders.demo.andina.target_projects', 6);

        $techNeeded = max(0, $techTarget - $techExistingCount);
        $andinaNeeded = max(0, $andinaTarget - $andinaExistingCount);

        $techExtra = $techNeeded > 0
            ? Project::factory()->count($techNeeded)->create(['tenant_id' => $tenants['tech']->id])
            : collect();

        $andinaExtra = $andinaNeeded > 0
            ? Project::factory()->count($andinaNeeded)->create(['tenant_id' => $tenants['andina']->id])
            : collect();

        $projects['tech'] = $techProjects->merge($techExtra);
        $projects['andina'] = $andinaProjects->merge($andinaExtra);

        $this->context['projects'] = $projects;
    }

    private function createProject($tenant, string $name, ?string $description = null): Project
    {
        return Project::firstOrCreate(
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
