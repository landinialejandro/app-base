<?php

// FILE: app/Support/Documents/DocumentSurfaceService.php | V4

namespace App\Support\Documents;

use App\Models\Order;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class DocumentSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            [
                'type' => 'embedded',
                'key' => 'documents.order.embedded',
                'label' => 'Documentos',
                'targets' => ['orders.show'],
                'priority' => 80,
                'view' => 'documents.partials.embedded-tabs',
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
        $documents = $order->documents ?? collect();

        return [
            'count' => method_exists($documents, 'count') ? $documents->count() : 0,
            'data' => [
                'documents' => $documents,
                'showParty' => true,
                'showAsset' => false,
                'showOrder' => false,
                'emptyMessage' => 'Esta orden no tiene documentos vinculados.',
                'tabsId' => 'order-documents-tabs',
                'trailQuery' => $trailQuery,
                'order' => $order,
            ],
        ];
    }
}
