<?php

// FILE: app/Support/Inventory/InventorySurfaceService.php | V12

namespace App\Support\Inventory;

use App\Models\Order;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class InventorySurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->embeddedOffer(
                key: 'inventory.operations',
                label: 'Operación',
                targets: ['orders.show'],
                slot: 'tab_panels',
                priority: 30,
                view: 'inventory.partials.embedded-movements',
                resolver: $this->resolveForOrder(...),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return [];
    }

    private function resolveForOrder(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

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
