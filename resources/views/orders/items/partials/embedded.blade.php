{{-- FILE: resources/views/orders/items/partials/embedded.blade.php | V4 --}}

@php
    $order = $order ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en esta orden.';
    $trailQuery = $trailQuery ?? [];
    $supportsProductsModule = $supportsProductsModule ?? true;
    $inventoryContext = $inventoryContext ?? null;
@endphp

<div class="tab-panel-stack">
    <x-tab-toolbar label="Ítems de la orden">
        <x-slot:tabs>
            <span class="tab-toolbar-title">
                Ítems
                @if ($items->count())
                    ({{ $items->count() }})
                @endif
            </span>
        </x-slot:tabs>

        <x-slot:actions>
            @if ($order)
                @can('update', $order)
                    @if (!\App\Support\Catalogs\OrderCatalog::isReadonlyStatus($order->status))
                        <a href="{{ route('orders.items.create', ['order' => $order] + $trailQuery) }}"
                            class="btn btn-success btn-sm">
                            <x-icons.plus />
                            <span>Agregar ítem</span>
                        </a>
                    @endif
                @endcan
            @endif
        </x-slot:actions>
    </x-tab-toolbar>

    <x-card class="list-card">
        @include('orders.items.partials.table', [
            'order' => $order,
            'items' => $items,
            'emptyMessage' => $emptyMessage,
            'trailQuery' => $trailQuery,
            'inventoryContext' => $inventoryContext,
        ])
    </x-card>

    <x-card>
        <div class="summary-inline-grid">
            <div class="summary-inline-card">
                <div class="summary-inline-label">Cantidad de ítems</div>
                <div class="summary-inline-value">{{ $items->count() }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Total orden</div>
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
