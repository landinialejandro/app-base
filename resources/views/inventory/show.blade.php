{{-- FILE: resources/views/inventory/show.blade.php | V13 --}}

@extends('layouts.app')

@section('title', 'Movimientos del artículo')

@section('content')
    @php
        use App\Support\Inventory\InventoryMovementService;
        use App\Support\Inventory\InventorySurfaceService;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;

        $movementRows = ($movementRows ?? collect())->values();
        $currentStock = isset($currentStock) ? (float) $currentStock : 0;
        $movementKind = $movementKind ?? '';
        $orderItem = $orderItem ?? null;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('inventory.index'));
        $tabsLabel = 'Secciones de inventory';

        $hostPack = app(InventorySurfaceService::class)->hostPack('inventory.show', $product, [
            'trailQuery' => $trailQuery,
        ]);

        $embedded = collect(app(ModuleSurfaceRegistry::class)->embeddedFor('inventory.show', $hostPack))->values();
        $linked = collect(app(ModuleSurfaceRegistry::class)->linkedFor('inventory.show', $hostPack))->values();

        $summaryItems = $linked->where('slot', 'summary_items')->values();
        $headerActions = $linked->where('slot', 'header_actions')->values();
        $detailItems = $embedded->where('slot', 'detail_items')->values();

        $kindLabels = [
            '' => 'Todos',
            InventoryMovementService::KIND_INGRESAR => 'Ingresos',
            InventoryMovementService::KIND_CONSUMIR => 'Consumos',
            InventoryMovementService::KIND_ENTREGAR => 'Entregas',
        ];

        $inventoryShowBaseQuery = ['product' => $product] + $trailQuery;

        if ($orderItem) {
            $inventoryShowBaseQuery['order_item_id'] = $orderItem->id;
        }

        $hostTabItems = collect([
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'movements',
                'label' => 'Movimientos',
                'priority' => 10,
                'count' => $movementRows->count(),
                'view' => 'inventory.partials.embedded-context',
                'data' => [
                    'contextType' => 'product',
                    'product' => $product,
                    'movementRows' => $movementRows,
                    'movementKind' => $movementKind,
                    'kindTabs' => collect($kindLabels)
                        ->map(function ($label, $value) use ($inventoryShowBaseQuery, $movementKind) {
                            return [
                                'label' => $label,
                                'url' => route(
                                    'inventory.show',
                                    $inventoryShowBaseQuery + ($value !== '' ? ['kind' => $value] : []),
                                ),
                                'is_active' => $movementKind === $value,
                            ];
                        })
                        ->values()
                        ->all(),
                    'emptyMessage' => $orderItem
                        ? 'No hay movimientos registrados para esta línea.'
                        : 'No hay movimientos registrados para este artículo.',
                    'trailQuery' => $trailQuery,
                ],
            ],
        ]);

        $surfaceTabItems = $embedded->where(fn($item) => ($item['slot'] ?? null) === 'tab_panels')->values();

        $tabItems = $hostTabItems->concat($surfaceTabItems)->sortBy(fn($item) => $item['priority'] ?? 999)->values();
    @endphp

    <x-page class="list-page">
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Movimientos de {{ $product->name }}">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-show-summary details-id="inventory-more-detail">

            <x-show-summary-item label="Saldo actual">
                {{ number_format($currentStock, 2, ',', '.') }}
            </x-show-summary-item>

            <x-show-summary-item label="Unidad">
                {{ $product->unit_label ?: '—' }}
            </x-show-summary-item>

            @if ($orderItem)
                <x-show-summary-item label="Línea filtrada">
                    #{{ $orderItem->position }} — {{ $orderItem->description ?: 'Sin descripción' }}
                </x-show-summary-item>
            @endif

            @foreach ($summaryItems as $surface)
                <x-show-summary-item :label="$surface['label'] ?? 'Relacionado'">
                    @include($surface['view'], $surface['data'] ?? [])
                </x-show-summary-item>
            @endforeach

            <x-slot:details>
                <x-show-summary-item-detail-block label="SKU">
                    {{ $product->sku ?: '—' }}
                </x-show-summary-item-detail-block>

                @if ($orderItem)
                    <x-show-summary-item-detail-block label="Order item">
                        #{{ $orderItem->id }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="Posición">
                        {{ $orderItem->position ?? '—' }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="Descripción de línea" full>
                        {{ $orderItem->description ?: '—' }}
                    </x-show-summary-item-detail-block>
                @endif

                <x-show-summary-item-detail-block label="Descripción" full>
                    {{ $product->description ?: '—' }}
                </x-show-summary-item-detail-block>

                @foreach ($detailItems as $surface)
                    <x-show-summary-item-detail-block :label="$surface['label'] ?? 'Relacionado'">
                        @include($surface['view'], $surface['data'] ?? [])
                    </x-show-summary-item-detail-block>
                @endforeach

            </x-slot:details>
        </x-show-summary>

        @if ($tabItems->isNotEmpty())
            <div class="tabs" data-tabs>
                <x-tab-toolbar :label="$tabsLabel">
                    <x-slot:tabs>
                        <x-horizontal-scroll :label="$tabsLabel">
                            @foreach ($tabItems as $tabItem)
                                <button type="button" class="tabs-link {{ $loop->first ? 'is-active' : '' }}"
                                    data-tab-link="{{ $tabItem['key'] }}" role="tab"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $tabItem['label'] ?? $tabItem['key'] }}

                                    @if (array_key_exists('count', $tabItem) && (int) $tabItem['count'] > 0)
                                        ({{ $tabItem['count'] }})
                                    @endif
                                </button>
                            @endforeach
                        </x-horizontal-scroll>
                    </x-slot:tabs>
                </x-tab-toolbar>

                @foreach ($tabItems as $tabItem)
                    <section class="tab-panel {{ $loop->first ? 'is-active' : '' }}"
                        data-tab-panel="{{ $tabItem['key'] }}" @unless ($loop->first) hidden @endunless>
                        <div class="tab-panel-stack">
                            @include($tabItem['view'], $tabItem['data'] ?? [])
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </x-page>
@endsection