<?php

// FILE: app/Support/Orders/OrderSurfaceService.php | V15

namespace App\Support\Orders;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OrderSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->linkedOffer(
                key: 'order.document.linked',
                label: 'Orden asociada',
                targets: ['documents.show'],
                slot: 'detail_items',
                priority: 25,
                view: 'orders.components.linked-order',
                resolver: $this->resolveLinkedForDocument(...),
            ),
            $this->embeddedOffer(
                key: 'orders.asset.embedded',
                label: 'Órdenes',
                targets: ['assets.show'],
                slot: 'tab_panels',
                priority: 70,
                view: 'orders.partials.embedded-tabs',
                resolver: fn (array $hostPack) => $this->resolveEmbedded(
                    $hostPack,
                    expectedRecordType: 'asset',
                    expectedClass: Asset::class,
                    tabsId: 'asset-orders-tabs',
                    emptyTabsId: 'asset-orders-tabs-empty',
                ),
            ),
            $this->embeddedOffer(
                key: 'orders.party.embedded',
                label: 'Órdenes',
                targets: ['parties.show'],
                slot: 'tab_panels',
                priority: 80,
                view: 'orders.partials.embedded-tabs',
                resolver: fn (array $hostPack) => $this->resolveEmbedded(
                    $hostPack,
                    expectedRecordType: 'party',
                    expectedClass: Party::class,
                    tabsId: 'party-orders-tabs',
                    emptyTabsId: 'party-orders-tabs-empty',
                ),
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

    private function resolveLinkedForDocument(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'document' || ! $record instanceof Document) {
            return [
                'data' => [
                    'linked' => [
                        'supported' => false,
                        'exists' => false,
                        'hidden' => true,
                        'readonly' => false,
                        'state' => 'hidden',
                        'show_url' => null,
                        'create_url' => null,
                        'label' => 'Orden asociada',
                        'text' => null,
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'linked' => OrderLinked::forOrder($record->order, $trailQuery, 'Orden asociada'),
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
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

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
                'showCounterparty' => true,
                'showAsset' => false,
                'emptyMessage' => 'Este activo no tiene órdenes vinculadas.',
                'createBaseQuery' => [
                    'asset_id' => $record->getKey(),
                    'kind' => OrderCatalog::KIND_SERVICE,
                ],
            ],
            'party' => [
                'showCounterparty' => false,
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
                'showCounterparty' => true,
                'showAsset' => false,
                'emptyMessage' => 'Este activo no tiene órdenes vinculadas.',
                'createBaseQuery' => [],
            ],
            'party' => [
                'showCounterparty' => false,
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
                ->with(['party', 'asset', 'items'])
                ->where('party_id', $record->getKey())
                ->latest()
                ->get();
        }

        return collect();
    }
}