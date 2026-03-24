{{-- FILE: resources/views/products/show.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Detalle del producto')

@section('content')
    @php
        use App\Support\Catalogs\ProductCatalog;
        use App\Support\Navigation\NavigationTrail;

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

            <a href="{{ $backUrl }}" class="btn btn-secondary">
                <x-icons.chevron-left />
                <span>Volver</span>
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
    </x-page>
@endsection
