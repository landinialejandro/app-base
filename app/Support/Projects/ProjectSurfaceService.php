<?php

// FILE: app/Support/Projects/ProjectSurfaceService.php | V3

namespace App\Support\Projects;

use App\Support\Modules\Contracts\ModuleSurfaceService;

class ProjectSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return [];
    }
}
