{{-- FILE: resources/views/products/show.blade.php | V20 --}}

@extends('layouts.app')

@section('title', 'Detalle del producto')

@section('content')
    @php
        use App\Support\Inventory\InventorySurfaceService;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Catalogs\ProductCatalog;
        use App\Support\Navigation\NavigationTrail;

        $attachments = $product->attachments ?? collect();

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
            ->sortBy(fn(array $item) => $item['priority'] ?? 999)
            ->values();

        $tabItems = $embedded
            ->where(fn($item) => ($item['slot'] ?? null) === 'tab_panels')
            ->sortBy(fn(array $item) => $item['priority'] ?? 999)
            ->values();

        $tabsLabel = 'Secciones del producto';
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

                        <button type="button" class="tabs-link {{ $tabItems->isEmpty() ? 'is-active' : '' }}"
                            data-tab-link="attachments" role="tab"
                            aria-selected="{{ $tabItems->isEmpty() ? 'true' : 'false' }}">
                            Adjuntos
                            @if ($attachments->count())
                                ({{ $attachments->count() }})
                            @endif
                        </button>
                    </x-horizontal-scroll>
                </x-slot:tabs>
            </x-tab-toolbar>

            @foreach ($tabItems as $tabItem)
                <section class="tab-panel {{ $loop->first ? 'is-active' : '' }}" data-tab-panel="{{ $tabItem['key'] }}"
                    @unless ($loop->first) hidden @endunless>
                    <div class="tab-panel-stack">
                        @include($tabItem['view'], $tabItem['data'] ?? [])
                    </div>
                </section>
            @endforeach

            <section class="tab-panel {{ $tabItems->isEmpty() ? 'is-active' : '' }}" data-tab-panel="attachments"
                @unless ($tabItems->isEmpty()) hidden @endunless>
                <div class="tab-panel-stack">
                    @include('attachments.partials.embedded', [
                        'attachments' => $attachments,
                        'attachable' => $product,
                        'attachableType' => 'product',
                        'attachableId' => $product->id,
                        'trailQuery' => $trailQuery,
                        'navigationTrail' => $navigationTrail,
                        'tabsId' => 'product-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>
        </div>
    </x-page>
@endsection
