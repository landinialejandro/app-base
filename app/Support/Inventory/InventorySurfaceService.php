<?php

// FILE: app/Support/Inventory/InventorySurfaceService.php | V10

namespace App\Support\Inventory;

use App\Models\Order;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class InventorySurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            [
                'type' => 'embedded',
                'key' => 'inventory.operations',
                'label' => 'Operación',
                'targets' => ['orders.show'],
                'priority' => 30,
                'view' => 'inventory.partials.embedded-movements',
                'needs' => ['record', 'trailQuery'],
                'resolver' => $this->resolveForOrder(...),
            ],
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return [];
    }

    private function resolveForOrder(array $hostPack): array
    {
        /** @var Order $order */
        $order = $hostPack['record'];
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        $inventoryContext = app(OrderInventoryContextResolver::class)->forOrder($order);

        return [
            'count' => collect($inventoryContext['items'] ?? [])->count(),
            'data' => [
                'order' => $order,
                'inventoryContext' => $inventoryContext,
                'trailQuery' => $trailQuery,
            ],
        ];
    }
}
