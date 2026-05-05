{{-- FILE: resources/views/products/show.blade.php | V24 --}}

@extends('layouts.app')

@section('title', 'Detalle del producto')

@section('content')
    @php
        use App\Support\Inventory\InventorySurfaceService;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Catalogs\ProductCatalog;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Ui\HostTabs;

        $attachments = $product->attachments ?? collect();
        $tabsLabel = 'Secciones del producto';

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('products.index'));

        $hostPack = app(InventorySurfaceService::class)->hostPack('products.show', $product, [
            'trailQuery' => $trailQuery,
        ]);

        $embedded = collect(app(ModuleSurfaceRegistry::class)->embeddedFor('products.show', $hostPack))->values();
        $linked = collect(app(ModuleSurfaceRegistry::class)->linkedFor('products.show', $hostPack))->values();

        $headerActions = $linked->where('slot', 'header_actions')->values();
        $summaryItems = $embedded
            ->where(fn($item) => ($item['slot'] ?? null) === 'summary_items')
            ->where(fn($item) => ($item['visible'] ?? true) !== false)
            ->sortBy(fn(array $item) => $item['priority'] ?? 999)
            ->values();

        $hostTabItems = collect([
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'attachments',
                'label' => 'Adjuntos',
                'priority' => 900,
                'count' => $attachments->count(),
                'view' => 'attachments.partials.embedded',
                'data' => [
                    'attachments' => $attachments,
                    'attachable' => $product,
                    'attachableType' => 'product',
                    'attachableId' => $product->id,
                    'trailQuery' => $trailQuery,
                    'navigationTrail' => $navigationTrail,
                    'tabsId' => 'product-attachments-tabs',
                    'createLabel' => 'Agregar adjunto',
                ],
            ],
        ]);

        $surfaceTabItems = $embedded
            ->where(fn($item) => ($item['slot'] ?? null) === 'tab_panels')
            ->where(fn($item) => ($item['visible'] ?? true) !== false)
            ->values();

        $tabItems = $surfaceTabItems->concat($hostTabItems)->sortBy(fn($item) => $item['priority'] ?? 999)->values();
        $activeTab = HostTabs::activeKey($tabItems, request()->query('return_tab'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del producto">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            @can('update', $product)
                <x-button-edit :href="route('products.edit', ['product' => $product] + $trailQuery)" />
            @endcan

            @can('delete', $product)
                <x-button-delete :action="route('products.destroy', ['product' => $product] + $trailQuery)" message="¿Eliminar producto?" />
            @endcan

            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-show-summary details-id="product-more-detail">
            <x-show-summary-item label="Nombre">
                {{ $product->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Precio">
                {{ $product->price !== null ? '$' . number_format((float) $product->price, 2, ',', '.') : '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Unidad">
                {{ $product->unit_label ?? '—' }}
            </x-show-summary-item>

            @foreach ($summaryItems as $surface)
                <x-show-summary-item :label="$surface['label'] ?? 'Relacionado'">
                    @include($surface['view'], $surface['data'] ?? [])
                </x-show-summary-item>
            @endforeach

            <x-slot:details>
                <x-show-summary-item-detail-block label="Tipo">
                    {{ ProductCatalog::label($product->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Activo">
                    <span class="status-badge {{ $product->is_active ? 'status-badge--done' : 'status-badge--cancelled' }}">
                        {{ $product->is_active ? 'Sí' : 'No' }}
                    </span>
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="SKU">
                    {{ $product->sku ?? '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Creado">
                    {{ $product->created_at?->format('d/m/Y H:i') ?? '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado">
                    {{ $product->updated_at?->format('d/m/Y H:i') ?? '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Descripción" full>
                    {{ $product->description ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <x-host-tabs :items="$tabItems" :active-tab="$activeTab" :label="$tabsLabel" />

        <x-dev-component-version name="products.show" version="V24" />
    </x-page>
@endsection