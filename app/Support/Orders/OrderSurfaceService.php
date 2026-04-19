<?php

// FILE: app/Support/Orders/OrderSurfaceService.php | V7

namespace App\Support\Orders;

use App\Models\Appointment;
use App\Models\Order;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class OrderSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            [
                'type' => 'linked',
                'key' => 'order.linked',
                'label' => AppointmentCatalog::orderLabel(),
                'targets' => ['appointments.show'],
                'slot' => 'summary_items',
                'priority' => 40,
                'view' => 'orders.components.linked-order-action',
                'needs' => ['record', 'recordType', 'trailQuery'],
                'resolver' => $this->resolveLinkedForAppointment(...),
            ],
        ];
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

    private function resolveLinkedForAppointment(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== 'appointment' || ! $record instanceof Appointment) {
            return [
                'data' => [
                    'action' => [
                        'supported' => false,
                        'linked' => false,
                        'can_view' => false,
                        'can_create' => false,
                        'show_url' => null,
                        'create_url' => null,
                        'label' => AppointmentCatalog::orderLabel(),
                        'contact_label' => AppointmentCatalog::contactLabel(),
                        'has_required_party' => false,
                        'linked_text' => null,
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'action' => OrderLinkedAction::forAppointment($record, $trailQuery, true),
                'variant' => 'summary',
            ],
        ];
    }
}
