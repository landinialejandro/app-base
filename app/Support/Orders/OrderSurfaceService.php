<?php

// FILE: app/Support/Orders/OrderSurfaceService.php | V18

namespace App\Support\Orders;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Support\Auth\Security;
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

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        $supportedHost = $this->supportedHosts()[$host] ?? null;

        if (! is_array($supportedHost)) {
            return [];
        }

        $expectedClass = $supportedHost['class'] ?? null;

        if (! is_string($expectedClass) || ! $record instanceof $expectedClass) {
            return [];
        }

        return [
            'host' => $host,
            'recordType' => $supportedHost['recordType'],
            'record' => $record,
            'trailQuery' => is_array($context['trailQuery'] ?? null)
                ? $context['trailQuery']
                : [],
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
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        $config = is_string($recordType)
            ? ($this->relatedHosts()[$recordType] ?? null)
            : null;

        if (! $this->matchesRelatedHost($record, $config)) {
            return $this->relatedEmbeddedPayload(
                orders: collect(),
                config: $this->emptyRelatedConfig(),
                trailQuery: $trailQuery,
            );
        }

        return $this->relatedEmbeddedPayload(
            orders: $this->ordersForRelatedHost($record, $config),
            config: $config,
            trailQuery: $trailQuery,
        );
    }

    private function relatedEmbeddedPayload(
        Collection $orders,
        array $config,
        array $trailQuery,
    ): array {
        return [
            'count' => $orders->count(),
            'data' => [
                'orders' => $orders,
                'tabsId' => $config['tabsId'],
                'trailQuery' => $trailQuery,
                'showCounterparty' => (bool) $config['showCounterparty'],
                'showAsset' => (bool) $config['showAsset'],
                'emptyMessage' => $config['emptyMessage'],
            ],
        ];
    }

    private function matchesRelatedHost(mixed $record, mixed $config): bool
    {
        if (! is_array($config)) {
            return false;
        }

        $expectedClass = $config['class'] ?? null;

        return is_string($expectedClass) && $record instanceof $expectedClass;
    }

    private function ordersForRelatedHost(Model $record, array $config): Collection
    {
        $filterColumn = $config['filterColumn'] ?? null;

        if (! is_string($filterColumn) || $filterColumn === '') {
            return collect();
        }

        return app(Security::class)
            ->scope(auth()->user(), 'orders.viewAny', Order::query())
            ->where($filterColumn, $record->getKey())
            ->latest()
            ->get();
    }

    private function supportedHosts(): array
    {
        return [
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
                'showAsset' => false,
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
