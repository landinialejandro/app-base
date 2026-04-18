<?php

// FILE: app/Support/Parties/PartySurfaceService.php | V3

namespace App\Support\Parties;

use App\Support\Modules\Contracts\ModuleSurfaceService;

class PartySurfaceService implements ModuleSurfaceService
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
