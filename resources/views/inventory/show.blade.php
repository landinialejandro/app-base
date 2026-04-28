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

        $detailsId = 'inventory-product-detail';
        $surfaceTabItems = ($tabItems ?? collect())->values();
        $tabsLabel = 'Secciones de inventario';
        $requestedTab = (string) request()->query('return_tab', '');

        $inventoryShowBaseQuery = $trailQuery;

        if (!empty($originLineType) && !empty($originLineId)) {
            $inventoryShowBaseQuery['origin_line_type'] = $originLineType;
            $inventoryShowBaseQuery['origin_line_id'] = $originLineId;
        }
        $hostTabItems = collect([
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'inventory-movements',
                'label' => 'Movimientos',
                'priority' => 10,
                'count' => $movementRows->count(),
                'view' => 'inventory.partials.movements-table',
                'data' => [
                    'movementRows' => $movementRows,
                    'emptyMessage' => 'No hay movimientos registrados para este artículo.',
                    'trailQuery' => $inventoryShowBaseQuery,
                ],
            ],
        ]);

        $tabItems = $hostTabItems->concat($surfaceTabItems)->sortBy(fn($item) => $item['priority'] ?? 999)->values();

        $availableTabKeys = $tabItems->pluck('key')->filter()->values()->all();

        $activeTab = in_array($requestedTab, $availableTabKeys, true)
            ? $requestedTab
            : $tabItems->first()['key'] ?? null;
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

        <x-host-tabs :items="$tabItems" :active-tab="$activeTab" :label="$tabsLabel" />
    </x-page>
@endsection
