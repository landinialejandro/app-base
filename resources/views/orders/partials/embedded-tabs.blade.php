{{-- FILE: resources/views/orders/partials/embedded-tabs.blade.php | V11 --}}

@php
    use App\Support\Catalogs\OrderCatalog;

    $orders = $orders ?? collect();

    $showCounterparty = $showCounterparty ?? ($showParty ?? false);
    $showAsset = $showAsset ?? false;

    $emptyMessage = $emptyMessage ?? 'No hay órdenes para mostrar.';
    $allLabel = $allLabel ?? 'Todas';

    $groups = OrderCatalog::groupLabels();
    $tabsId = $tabsId ?? 'orders-tabs-' . uniqid();
    $trailQuery = $trailQuery ?? [];
@endphp

<div class="tabs" data-tabs>
    <x-tab-toolbar label="Tipos de órdenes">
        <x-slot:tabs>
            <x-horizontal-scroll label="Tipos de órdenes">
                <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
                    aria-selected="true">
                    {{ $allLabel }}
                    @if ($orders->count())
                        ({{ $orders->count() }})
                    @endif
                </button>

                @foreach ($groups as $value => $label)
                    @php
                        $groupOrders = $orders->where('group', $value)->values();
                    @endphp

                    <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                        role="tab" aria-selected="false">
                        {{ $label }}
                        @if ($groupOrders->count())
                            ({{ $groupOrders->count() }})
                        @endif
                    </button>
                @endforeach
            </x-horizontal-scroll>
        </x-slot:tabs>
    </x-tab-toolbar>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('orders.partials.table', [
                    'orders' => $orders,
                    'showCounterparty' => $showCounterparty,
                    'showAsset' => $showAsset,
                    'emptyMessage' => $emptyMessage,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($groups as $value => $label)
        @php
            $groupOrders = $orders->where('group', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('orders.partials.table', [
                        'orders' => $groupOrders,
                        'showCounterparty' => $showCounterparty,
                        'showAsset' => $showAsset,
                        'emptyMessage' => "No hay órdenes de tipo {$label} para mostrar.",
                        'trailQuery' => $trailQuery,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>

<x-dev-component-version name="orders.partials.embedded-tabs" version="V11" align="right" />