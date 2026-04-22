{{-- FILE: resources/views/orders/show.blade.php | V40 --}}

@extends('layouts.app')

@section('title', 'Detalle de la orden')

@section('content')
    @php
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Orders\OrderSurfaceService;

        $items = $order->items ?? collect();

        $supportsProductsModule = $supportsProductsModule ?? true;
        $supportsTasksModule = $supportsTasksModule ?? true;

        $pageTitle = 'Detalle de la orden';
        $detailsId = 'order-more-detail';
        $tabsLabel = 'Secciones de la orden';

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.index'));

        $hostPack = app(OrderSurfaceService::class)->hostPack('orders.show', $order, [
            'trailQuery' => $trailQuery,
        ]);

        $embedded = collect(app(ModuleSurfaceRegistry::class)->embeddedFor('orders.show', $hostPack))->values();
        $linked = collect(app(ModuleSurfaceRegistry::class)->linkedFor('orders.show', $hostPack))->values();

        $headerActions = $linked->where('slot', 'header_actions')->values();
        $summaryItems = $linked->where('slot', 'summary_items')->values();
        $detailItems = $linked->where('slot', 'detail_items')->values();

        $hostTabItems = collect([
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'items',
                'label' => 'Ítems',
                'priority' => 10,
                'count' => $items->count(),
                'view' => 'orders.items.partials.embedded',
                'data' => [
                    'order' => $order,
                    'items' => $items,
                    'trailQuery' => $trailQuery,
                    'supportsProductsModule' => $supportsProductsModule,
                ],
            ],
        ]);

        $surfaceTabItems = $embedded->where(fn($item) => ($item['slot'] ?? 'tab_panels') === 'tab_panels')->values();

        $tabItems = $hostTabItems->concat($surfaceTabItems)->sortBy(fn($item) => $item['priority'] ?? 999)->values();
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$pageTitle">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            @can('update', $order)
                <x-button-edit :href="route('orders.edit', ['order' => $order] + $trailQuery)" />
            @endcan

            @can('delete', $order)
                <x-button-delete :action="route('orders.destroy', ['order' => $order] + $trailQuery)" message="¿Eliminar orden?" />
            @endcan

            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-show-summary details-id="{{ $detailsId }}">
            @foreach ($summaryItems as $surface)
                <x-show-summary-item :label="$surface['label'] ?? 'Relacionado'">
                    @include($surface['view'], $surface['data'] ?? [])
                </x-show-summary-item>
            @endforeach

            <x-show-summary-item label="Número">
                {{ $order->number ?: '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Estado">
                <span class="status-badge {{ OrderCatalog::badgeClass($order->status) }}">
                    {{ OrderCatalog::statusLabel($order->status) }}
                </span>
            </x-show-summary-item>

            <x-slot:details>
                @foreach ($detailItems as $detailItem)
                    <x-show-summary-item-detail-block :label="$detailItem['label'] ?? 'Relacionado'">
                        @include($detailItem['view'], $detailItem['data'] ?? [])
                    </x-show-summary-item-detail-block>
                @endforeach

                <x-show-summary-item-detail-block label="Tipo">
                    {{ OrderCatalog::kindLabel($order->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Fecha">
                    {{ $order->ordered_at?->format('d/m/Y') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Creado">
                    {{ $order->created_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado">
                    {{ $order->updated_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $order->notes ?: '—' }}
                </x-show-summary-item-detail-block>
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
