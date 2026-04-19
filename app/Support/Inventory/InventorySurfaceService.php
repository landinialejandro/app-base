<?php

// FILE: app/Support/Inventory/InventorySurfaceService.php | V11

namespace App\Support\Inventory;

use App\Models\Order;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class InventorySurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            $this->embeddedOffer(
                key: 'inventory.operations',
                target: 'orders.show',
                label: 'Operación',
                priority: 30,
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return [];
    }

    private function embeddedOffer(
        string $key,
        string $target,
        string $label,
        int $priority,
    ): array {
        return [
            'type' => 'embedded',
            'key' => $key,
            'label' => $label,
            'targets' => [$target],
            'slot' => 'tab_panels',
            'priority' => $priority,
            'view' => 'inventory.partials.embedded-movements',
            'needs' => ['record', 'recordType', 'trailQuery'],
            'resolver' => $this->resolveForOrder(...),
        ];
    }

    private function resolveForOrder(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== 'order' || ! $record instanceof Order) {
            return [
                'count' => 0,
                'data' => [
                    'order' => null,
                    'inventoryContext' => [
                        'items' => [],
                    ],
                    'trailQuery' => $trailQuery,
                ],
            ];
        }

        $inventoryContext = app(OrderInventoryContextResolver::class)->forOrder($record);

        return [
            'count' => collect($inventoryContext['items'] ?? [])->count(),
            'data' => [
                'order' => $record,
                'inventoryContext' => $inventoryContext,
                'trailQuery' => $trailQuery,
            ],
        ];
    }
}
