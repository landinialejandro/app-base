<?php

// FILE: app/Support/Orders/OrderSurfaceService.php | V8

namespace App\Support\Orders;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Order;
use App\Support\Auth\Security;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
            $this->embeddedOffer(
                key: 'orders.asset.embedded',
                target: 'assets.show',
                expectedRecordType: 'asset',
                expectedClass: Asset::class,
                tabsId: 'asset-orders-tabs',
                emptyTabsId: 'asset-orders-tabs-empty',
                priority: 70,
            ),
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

    private function embeddedOffer(
        string $key,
        string $target,
        string $expectedRecordType,
        string $expectedClass,
        string $tabsId,
        string $emptyTabsId,
        int $priority,
    ): array {
        return [
            'type' => 'embedded',
            'key' => $key,
            'label' => 'Órdenes',
            'targets' => [$target],
            'slot' => 'tab_panels',
            'priority' => $priority,
            'view' => 'orders.partials.embedded-tabs',
            'needs' => ['record', 'recordType', 'trailQuery'],
            'resolver' => fn (array $hostPack) => $this->resolveEmbedded(
                $hostPack,
                expectedRecordType: $expectedRecordType,
                expectedClass: $expectedClass,
                tabsId: $tabsId,
                emptyTabsId: $emptyTabsId,
            ),
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

    private function resolveEmbedded(
        array $hostPack,
        string $expectedRecordType,
        string $expectedClass,
        string $tabsId,
        string $emptyTabsId,
    ): array {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== $expectedRecordType || ! $record instanceof $expectedClass) {
            return $this->emptyEmbeddedPayload(
                recordType: $expectedRecordType,
                tabsId: $emptyTabsId,
                trailQuery: $trailQuery,
            );
        }

        return $this->buildEmbeddedPayload(
            record: $record,
            recordType: $expectedRecordType,
            tabsId: $tabsId,
            trailQuery: $trailQuery,
        );
    }

    private function buildEmbeddedPayload(
        Model $record,
        string $recordType,
        string $tabsId,
        array $trailQuery,
    ): array {
        $orders = $this->ordersFor($record, $recordType);

        return [
            'count' => $orders->count(),
            'data' => array_merge(
                [
                    'orders' => $orders,
                    'tabsId' => $tabsId,
                    'trailQuery' => $trailQuery,
                ],
                $this->orderViewConfig($record, $recordType),
            ),
        ];
    }

    private function emptyEmbeddedPayload(
        string $recordType,
        string $tabsId,
        array $trailQuery,
    ): array {
        return [
            'count' => 0,
            'data' => array_merge(
                [
                    'orders' => collect(),
                    'tabsId' => $tabsId,
                    'trailQuery' => $trailQuery,
                ],
                $this->emptyOrderViewConfig($recordType),
            ),
        ];
    }

    private function orderViewConfig(Model $record, string $recordType): array
    {
        return match ($recordType) {
            'asset' => [
                'showParty' => true,
                'showAsset' => false,
                'emptyMessage' => 'Este activo no tiene órdenes vinculadas.',
                'createBaseQuery' => [
                    'asset_id' => $record->getKey(),
                    'kind' => OrderCatalog::KIND_SERVICE,
                ],
            ],
            default => [],
        };
    }

    private function emptyOrderViewConfig(string $recordType): array
    {
        return match ($recordType) {
            'asset' => [
                'showParty' => true,
                'showAsset' => false,
                'emptyMessage' => 'Este activo no tiene órdenes vinculadas.',
                'createBaseQuery' => [],
            ],
            default => [],
        };
    }

    private function ordersFor(Model $record, string $recordType): Collection
    {
        if ($recordType === 'asset' && $record instanceof Asset) {
            return app(Security::class)
                ->scope(auth()->user(), 'orders.viewAny', Order::query())
                ->with('party')
                ->where('asset_id', $record->getKey())
                ->latest()
                ->get();
        }

        return collect();
    }
}
