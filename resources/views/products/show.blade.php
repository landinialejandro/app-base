{{-- FILE: resources/views/products/show.blade.php | V19 --}}

@extends('layouts.app')

@section('title', 'Detalle del producto')

@section('content')
    @php
        use App\Support\Catalogs\ProductCatalog;
        use App\Support\Navigation\NavigationTrail;

        $attachments = $product->attachments ?? collect();
        $inventoryMovements = ($inventoryMovements ?? collect())->values();
        $currentStock = isset($currentStock) ? (float) $currentStock : 0;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('products.index'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del producto">
            @can('update', $product)
                <a href="{{ route('products.edit', ['product' => $product] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $product)
                <form method="POST" action="{{ route('products.destroy', ['product' => $product] + $trailQuery) }}"
                    class="inline-form" data-action="app-confirm-submit" data-confirm-message="¿Eliminar producto?">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endcan

            <a href="{{ $backUrl }}" class="btn btn-secondary" title="Volver" aria-label="Volver">
                <x-icons.chevron-left />
            </a>
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

        @if ($product->kind === ProductCatalog::KIND_PRODUCT)
            <x-card>
                <div class="summary-inline-grid">
                    <div class="summary-inline-card">
                        <div class="summary-inline-label">Stock actual</div>
                        <div class="summary-inline-value">{{ number_format($currentStock, 2, ',', '.') }}</div>
                    </div>

                    <div class="summary-inline-card">
                        <div class="summary-inline-label">Último movimiento</div>
                        <div class="summary-inline-value">
                            @if ($inventoryMovements->isNotEmpty())
                                {{ $inventoryMovements->first()->created_at?->format('d/m/Y H:i') ?? '—' }}
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="summary-inline-card">
                        <div class="summary-inline-label">Inventory</div>
                        <div class="summary-inline-value">
                            <a href="{{ route('inventory.show', ['product' => $product] + $trailQuery) }}">
                                Abrir ficha
                            </a>
                        </div>
                    </div>
                </div>

                <div class="form-help mt-3">
                    El historial y los movimientos manuales de este artículo se gestionan desde la ficha de inventory.
                </div>
            </x-card>
        @endif

        <div class="tabs" data-tabs>
            <x-tab-toolbar label="Secciones del producto">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones del producto">
                        <button type="button" class="tabs-link is-active" data-tab-link="attachments" role="tab"
                            aria-selected="true">
                            Adjuntos
                            @if ($attachments->count())
                                ({{ $attachments->count() }})
                            @endif
                        </button>
                    </x-horizontal-scroll>
                </x-slot:tabs>
            </x-tab-toolbar>

            <section class="tab-panel is-active" data-tab-panel="attachments">
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
