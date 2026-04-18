<?php

// FILE: app/Support/Products/ProductSurfaceService.php | V3

namespace App\Support\Products;

use App\Support\Modules\Contracts\ModuleSurfaceService;

class ProductSurfaceService implements ModuleSurfaceService
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
