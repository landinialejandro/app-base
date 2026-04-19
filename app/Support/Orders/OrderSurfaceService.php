<?php

// FILE: app/Support/Orders/OrderSurfaceService.php | V11

namespace App\Support\Orders;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Models\Task;
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
            [
                'type' => 'linked',
                'key' => 'order.task.header',
                'label' => 'Orden',
                'targets' => ['tasks.show'],
                'slot' => 'header_actions',
                'priority' => 35,
                'view' => 'orders.components.linked-order-action',
                'needs' => ['record', 'recordType', 'trailQuery'],
                'resolver' => $this->resolveLinkedForTaskHeader(...),
            ],
            [
                'type' => 'linked',
                'key' => 'order.task.linked',
                'label' => 'Orden asociada',
                'targets' => ['tasks.show'],
                'slot' => 'detail_items',
                'priority' => 25,
                'view' => 'orders.components.linked-order-action',
                'needs' => ['record', 'recordType', 'trailQuery'],
                'resolver' => $this->resolveLinkedForTaskDetail(...),
            ],
            [
                'type' => 'linked',
                'key' => 'order.document.linked',
                'label' => 'Orden asociada',
                'targets' => ['documents.show'],
                'slot' => 'detail_items',
                'priority' => 25,
                'view' => 'orders.components.linked-order-action',
                'needs' => ['record', 'recordType', 'trailQuery'],
                'resolver' => $this->resolveLinkedForDocument(...),
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
            $this->embeddedOffer(
                key: 'orders.party.embedded',
                target: 'parties.show',
                expectedRecordType: 'party',
                expectedClass: Party::class,
                tabsId: 'party-orders-tabs',
                emptyTabsId: 'party-orders-tabs-empty',
                priority: 80,
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host === 'orders.show' && $record instanceof Order) {
            return [
                'host' => $host,
                'recordType' => 'order',
                'record' => $record,
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        if ($host === 'parties.show' && $record instanceof Party) {
            return [
                'host' => $host,
                'recordType' => 'party',
                'record' => $record,
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        if ($host === 'assets.show' && $record instanceof Asset) {
            return [
                'host' => $host,
                'recordType' => 'asset',
                'record' => $record,
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        if ($host === 'tasks.show' && $record instanceof Task) {
            return [
                'host' => $host,
                'recordType' => 'task',
                'record' => $record,
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        if ($host === 'documents.show' && $record instanceof Document) {
            return [
                'host' => $host,
                'recordType' => 'document',
                'record' => $record,
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        return [];
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

    private function resolveLinkedForTaskHeader(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== 'task' || ! $record instanceof Task) {
            return [
                'data' => [
                    'action' => [
                        'supported' => false,
                        'linked' => false,
                        'can_view' => false,
                        'can_create' => false,
                        'show_url' => null,
                        'create_url' => null,
                        'label' => 'Orden',
                        'contact_label' => 'Contacto',
                        'has_required_party' => false,
                        'linked_text' => null,
                    ],
                    'variant' => 'button',
                ],
            ];
        }

        return [
            'data' => [
                'action' => OrderLinkedAction::forTask(
                    $record,
                    $trailQuery,
                    (bool) (auth()->user() && auth()->user()->can('update', $record)),
                ),
                'variant' => 'button',
            ],
        ];
    }

    private function resolveLinkedForTaskDetail(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== 'task' || ! $record instanceof Task) {
            return [
                'data' => [
                    'action' => [
                        'supported' => false,
                        'linked' => false,
                        'can_view' => false,
                        'can_create' => false,
                        'show_url' => null,
                        'create_url' => null,
                        'label' => 'Orden asociada',
                        'contact_label' => 'Contacto',
                        'has_required_party' => false,
                        'linked_text' => null,
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'action' => OrderLinkedAction::forTask(
                    $record,
                    $trailQuery,
                    (bool) (auth()->user() && auth()->user()->can('update', $record)),
                ),
                'variant' => 'summary',
            ],
        ];
    }

    private function resolveLinkedForDocument(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== 'document' || ! $record instanceof Document) {
            return [
                'data' => [
                    'action' => [
                        'supported' => false,
                        'linked' => false,
                        'can_view' => false,
                        'can_create' => false,
                        'show_url' => null,
                        'create_url' => null,
                        'label' => 'Orden asociada',
                        'contact_label' => 'Contacto',
                        'has_required_party' => false,
                        'linked_text' => null,
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        $order = $record->order;

        return [
            'data' => [
                'action' => [
                    'supported' => true,
                    'linked' => (bool) $order,
                    'can_view' => (bool) ($order && auth()->user()?->can('view', $order)),
                    'can_create' => false,
                    'show_url' => $order ? route('orders.show', ['order' => $order] + $trailQuery) : null,
                    'create_url' => null,
                    'label' => 'Orden asociada',
                    'contact_label' => 'Contacto',
                    'has_required_party' => false,
                    'linked_text' => $order
                        ? ($order->number ?: 'Orden #'.$order->id)
                        : null,
                ],
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
            'party' => [
                'showParty' => false,
                'showAsset' => true,
                'emptyMessage' => 'Este contacto no tiene órdenes vinculadas.',
                'createBaseQuery' => [
                    'party_id' => $record->getKey(),
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
            'party' => [
                'showParty' => false,
                'showAsset' => true,
                'emptyMessage' => 'Este contacto no tiene órdenes vinculadas.',
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

        if ($recordType === 'party' && $record instanceof Party) {
            return app(Security::class)
                ->scope(auth()->user(), 'orders.viewAny', Order::query())
                ->with(['party', 'asset', 'task', 'items'])
                ->where('party_id', $record->getKey())
                ->latest()
                ->get();
        }

        return collect();
    }
}
