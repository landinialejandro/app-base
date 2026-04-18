<?php

// FILE: app/Support/Tasks/TaskSurfaceService.php | V3

namespace App\Support\Tasks;

use App\Support\Modules\Contracts\ModuleSurfaceService;

class TaskSurfaceService implements ModuleSurfaceService
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
