<?php

// FILE: app/Support/Attachments/AttachmentSurfaceService.php | V6

namespace App\Support\Attachments;

use App\Models\Order;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class AttachmentSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            [
                'type' => 'embedded',
                'key' => 'attachments.order.embedded',
                'label' => 'Adjuntos',
                'targets' => ['orders.show'],
                'priority' => 90,
                'view' => 'attachments.partials.embedded',
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
        $attachments = $order->attachments ?? collect();

        return [
            'count' => method_exists($attachments, 'count') ? $attachments->count() : 0,
            'data' => [
                'attachments' => $attachments,
                'attachable' => $order,
                'attachableType' => 'order',
                'attachableId' => $order->id,
                'trailQuery' => $trailQuery,
                'tabsId' => 'order-attachments-tabs',
                'createLabel' => 'Agregar adjunto',
            ],
        ];
    }
}
