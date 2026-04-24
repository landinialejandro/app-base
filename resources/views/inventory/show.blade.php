{{-- FILE: resources/views/inventory/show.blade.php | V6 --}}

@extends('layouts.app')

@section('title', 'Inventario del artículo')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $movementRows = ($movementRows ?? collect())->values();
        $summaryItems = ($summaryItems ?? collect())->values();
        $headerActions = ($headerActions ?? collect())->values();
        $detailItems = ($detailItems ?? collect())->values();
        $tabItems = ($tabItems ?? collect())->values();

        $detailsId = 'inventory-product-detail';

        $inventoryShowBaseQuery = $trailQuery;

        if (!empty($originLineType) && !empty($originLineId)) {
            $inventoryShowBaseQuery['origin_line_type'] = $originLineType;
            $inventoryShowBaseQuery['origin_line_id'] = $originLineId;
        }
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Inventario: {{ $product->name }}">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            <x-button-back :href="NavigationTrail::previousUrl($navigationTrail, route('inventory.index'))" />
        </x-page-header>

        <x-show-summary :details-id="$detailsId">
            @foreach ($summaryItems as $surface)
                <x-show-summary-item :label="$surface['label'] ?? 'Dato'">
                    @include($surface['view'], $surface['data'] ?? [])
                </x-show-summary-item>
            @endforeach

            <x-show-summary-item label="Stock actual">
                {{ number_format((float) $currentStock, 2, ',', '.') }}
            </x-show-summary-item>

            <x-show-summary-item label="SKU">
                {{ $product->sku ?: '—' }}
            </x-show-summary-item>

            <x-slot:details>
                @foreach ($detailItems as $surface)
                    <x-show-summary-item-detail-block :label="$surface['label'] ?? 'Dato'">
                        @include($surface['view'], $surface['data'] ?? [])
                    </x-show-summary-item-detail-block>
                @endforeach

                <x-show-summary-item-detail-block label="Unidad">
                    {{ $product->unit_label ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Descripción" full>
                    {{ $product->description ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <div class="tabs" data-tabs>
            <x-tab-toolbar label="Secciones de inventario">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones de inventario">
                        <button type="button" class="tabs-link is-active" data-tab-link="inventory-movements"
                            role="tab" aria-selected="true">
                            Movimientos
                            @if ($movementRows->count())
                                ({{ $movementRows->count() }})
                            @endif
                        </button>

                        @foreach ($tabItems as $tabItem)
                            <button type="button" class="tabs-link" data-tab-link="{{ $tabItem['key'] }}" role="tab"
                                aria-selected="false">
                                {{ $tabItem['label'] ?? $tabItem['key'] }}

                                @if (array_key_exists('count', $tabItem) && (int) $tabItem['count'] > 0)
                                    ({{ $tabItem['count'] }})
                                @endif
                            </button>
                        @endforeach
                    </x-horizontal-scroll>
                </x-slot:tabs>
            </x-tab-toolbar>

            <section class="tab-panel is-active" data-tab-panel="inventory-movements">
                <div class="tab-panel-stack">
                    @include('inventory.partials.movements-table', [
                        'movementRows' => $movementRows,
                        'emptyMessage' => 'No hay movimientos registrados para este artículo.',
                        'trailQuery' => $inventoryShowBaseQuery,
                    ])
                </div>
            </section>

            @foreach ($tabItems as $tabItem)
                <section class="tab-panel" data-tab-panel="{{ $tabItem['key'] }}" hidden>
                    <div class="tab-panel-stack">
                        @include($tabItem['view'], $tabItem['data'] ?? [])
                    </div>
                </section>
            @endforeach
        </div>
    </x-page>
@endsection