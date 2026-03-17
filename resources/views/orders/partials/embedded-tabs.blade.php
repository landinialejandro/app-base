{{-- FILE: resources/views/orders/partials/embedded-tabs.blade.php --}}

@php
    use App\Support\Catalogs\OrderCatalog;

    $orders = $orders ?? collect();

    $showParty = $showParty ?? false;
    $showAsset = $showAsset ?? true;

    $emptyMessage = $emptyMessage ?? 'No hay órdenes para mostrar.';
    $allLabel = $allLabel ?? 'Todas';

    $kinds = OrderCatalog::kindLabels();
    $tabsId = $tabsId ?? 'orders-tabs-' . uniqid();
@endphp

<div class="tabs" data-tabs>
    <div class="tabs-nav" role="tablist" aria-label="Tipos de órdenes">
        <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
            aria-selected="true">
            {{ $allLabel }}
            @if ($orders->count())
                ({{ $orders->count() }})
            @endif
        </button>

        @foreach ($kinds as $value => $label)
            @php
                $kindOrders = $orders->where('kind', $value)->values();
            @endphp

            <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                role="tab" aria-selected="false">
                {{ $label }}
                @if ($kindOrders->count())
                    ({{ $kindOrders->count() }})
                @endif
            </button>
        @endforeach
    </div>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('orders.partials.table', [
                    'orders' => $orders,
                    'showParty' => $showParty,
                    'showAsset' => $showAsset,
                    'emptyMessage' => $emptyMessage,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($kinds as $value => $label)
        @php
            $kindOrders = $orders->where('kind', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('orders.partials.table', [
                        'orders' => $kindOrders,
                        'showParty' => $showParty,
                        'showAsset' => $showAsset,
                        'emptyMessage' => "No hay órdenes de tipo {$label} para mostrar.",
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>
