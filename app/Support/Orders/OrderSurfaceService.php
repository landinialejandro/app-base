<?php

// FILE: app/Support/Orders/OrderSurfaceService.php | V5

namespace App\Support\Orders;

use App\Models\Order;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class OrderSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host !== 'orders.show') {
            return [];
        }

        if (! $record instanceof Order) {
            return [];
        }

        return [
            'host' => $host,
            'recordType' => 'order',
            'record' => $record,
            'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
        ];
    }
}
