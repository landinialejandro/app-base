<?php

// FILE: app/Support/Assets/AssetSurfaceService.php | V3

namespace App\Support\Assets;

use App\Support\Modules\Contracts\ModuleSurfaceService;

class AssetSurfaceService implements ModuleSurfaceService
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
