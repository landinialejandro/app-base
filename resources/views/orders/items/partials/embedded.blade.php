{{-- FILE: resources/views/orders/items/partials/embedded.blade.php | V7 --}}

@php
    use App\Support\Catalogs\OrderCatalog;
    use App\Support\Catalogs\OrderItemCatalog;

    $order = $order ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en esta orden.';
    $trailQuery = $trailQuery ?? [];
    $supportsProductsModule = $supportsProductsModule ?? true;

    $statuses = [
        OrderItemCatalog::STATUS_PENDING => OrderItemCatalog::statusLabel(OrderItemCatalog::STATUS_PENDING),
        OrderItemCatalog::STATUS_PARTIAL => OrderItemCatalog::statusLabel(OrderItemCatalog::STATUS_PARTIAL),
        OrderItemCatalog::STATUS_COMPLETED => OrderItemCatalog::statusLabel(OrderItemCatalog::STATUS_COMPLETED),
        OrderItemCatalog::STATUS_CANCELLED => OrderItemCatalog::statusLabel(OrderItemCatalog::STATUS_CANCELLED),
    ];

    $tabsId = $tabsId ?? 'order-items-tabs-' . uniqid();
@endphp

<div class="tabs" data-tabs>
    <x-tab-toolbar label="Estados de ítems">
        <x-slot:tabs>
            <x-horizontal-scroll label="Estados de ítems">
                <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
                    aria-selected="true">
                    Todos
                    @if ($items->count())
                        ({{ $items->count() }})
                    @endif
                </button>

                @foreach ($statuses as $value => $label)
                    @php
                        $statusItems = $items->where('status', $value)->values();
                    @endphp

                    <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                        role="tab" aria-selected="false">
                        {{ $label }}
                        @if ($statusItems->count())
                            ({{ $statusItems->count() }})
                        @endif
                    </button>
                @endforeach
            </x-horizontal-scroll>
        </x-slot:tabs>

        <x-slot:actions>
            @if ($order)
                @can('update', $order)
                    @if (!OrderCatalog::isReadonlyStatus($order->status))
                        <x-button-create :href="route('orders.items.create', ['order' => $order] + $trailQuery)" label="Agregar ítem" class="btn-sm" />
                    @endif
                @endcan
            @endif
        </x-slot:actions>
    </x-tab-toolbar>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('orders.items.partials.table', [
                    'order' => $order,
                    'items' => $items,
                    'emptyMessage' => $emptyMessage,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($statuses as $value => $label)
        @php
            $statusItems = $items->where('status', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('orders.items.partials.table', [
                        'order' => $order,
                        'items' => $statusItems,
                        'emptyMessage' => "No hay ítems en estado {$label} para mostrar.",
                        'trailQuery' => $trailQuery,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach

    <x-card>
        <div class="summary-inline-grid">
            <div class="summary-inline-card">
                <div class="summary-inline-label">Cantidad de ítems</div>
                <div class="summary-inline-value">{{ $items->count() }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Total estructural</div>
                <div class="summary-inline-value">
                    ${{ number_format((float) ($order?->total ?? 0), 2, ',', '.') }}
                </div>
            </div>
        </div>

        @unless ($supportsProductsModule)
            <div class="form-help mt-3">
                El módulo de productos no está habilitado para esta empresa. Los ítems pueden cargarse manualmente.
            </div>
        @endunless
    </x-card>
</div>