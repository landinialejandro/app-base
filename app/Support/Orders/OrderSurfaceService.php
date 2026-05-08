<?php

// FILE: app/Support/Orders/OrderSurfaceService.php | V19

namespace App\Support\Orders;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Support\Modules\Concerns\BuildsHostPacks;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Concerns\ResolvesRelatedEmbeddedSurfaces;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class OrderSurfaceService implements ModuleSurfaceService
{
    use BuildsHostPacks;
    use BuildsSurfaceOffers;
    use ResolvesRelatedEmbeddedSurfaces;

    public function offers(): array
    {
        return [
            $this->linkedOffer(
                key: 'orders.associated.linked',
                label: 'Orden asociada',
                targets: ['documents.show'],
                slot: 'detail_items',
                priority: 25,
                view: 'orders.components.linked-order',
                resolver: $this->resolveAssociatedLinked(...),
            ),
            $this->embeddedOffer(
                key: 'orders.related.embedded',
                label: 'Órdenes',
                targets: ['assets.show', 'parties.show'],
                slot: 'tab_panels',
                priority: 80,
                view: 'orders.partials.embedded-tabs',
                resolver: $this->resolveRelatedEmbedded(...),
            ),
        ];
    }

    private function supportedHosts(): array
    {
        return [
            'orders.form' => [
                'recordType' => 'order',
                'class' => Order::class,
                'allowNullRecord' => true,
            ],
            'orders.show' => [
                'recordType' => 'order',
                'class' => Order::class,
            ],
            'parties.show' => [
                'recordType' => 'party',
                'class' => Party::class,
            ],
            'assets.show' => [
                'recordType' => 'asset',
                'class' => Asset::class,
            ],
            'documents.show' => [
                'recordType' => 'document',
                'class' => Document::class,
            ],
        ];
    }

    private function resolveAssociatedLinked(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        $order = match ($recordType) {
            'document' => $record instanceof Document ? $record->order : null,
            default => null,
        };

        return [
            'data' => [
                'linked' => OrderLinked::forOrder(
                    $order,
                    $trailQuery,
                    'Orden asociada',
                ),
                'variant' => 'summary',
            ],
        ];
    }

    private function resolveRelatedEmbedded(array $hostPack): array
    {
        return $this->relatedEmbeddedSurfacePayload(
            hostPack: $hostPack,
            relatedHosts: $this->relatedHosts(),
            emptyConfig: $this->emptyRelatedConfig(),
            recordsResolver: fn ($record, array $config) => $this->scopedRelatedRecordsFor(
                record: $record,
                config: $config,
                ability: 'orders.viewAny',
                modelClass: Order::class,
            ),
            payloadBuilder: fn ($orders, array $config, array $trailQuery) => $this->embeddedRecordsPayload(
                records: $orders,
                recordsKey: 'orders',
                config: $config,
                trailQuery: $trailQuery,
                extraData: [
                    'showCounterparty' => (bool) ($config['showCounterparty'] ?? false),
                    'showAsset' => (bool) ($config['showAsset'] ?? false),
                ],
            ),
        );
    }

    private function relatedHosts(): array
    {
        return [
            'asset' => [
                'class' => Asset::class,
                'filterColumn' => 'asset_id',
                'tabsId' => 'asset-orders-tabs',
                'showCounterparty' => true,
                'showAsset' => false,
                'emptyMessage' => 'Este activo no tiene órdenes vinculadas.',
            ],
            'party' => [
                'class' => Party::class,
                'filterColumn' => 'party_id',
                'tabsId' => 'party-orders-tabs',
                'showCounterparty' => false,
                'showAsset' => true,
                'emptyMessage' => 'Este contacto no tiene órdenes vinculadas.',
            ],
        ];
    }

    private function emptyRelatedConfig(): array
    {
        return [
            'tabsId' => 'orders-tabs-empty',
            'showCounterparty' => false,
            'showAsset' => false,
            'emptyMessage' => 'No hay órdenes para mostrar.',
        ];
    }
}
