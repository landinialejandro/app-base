{{-- FILE: resources/views/products/show.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Detalle del producto')

@section('content')

    @php
        use App\Support\Catalogs\ProductCatalog;
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Productos', 'url' => route('products.index')],
            ['label' => $product->name],
        ]" />

        <x-page-header title="Detalle del producto">
            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">
                <x-icons.pencil />
                <span>Editar</span>
            </a>

            <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline-form"
                data-action="app-confirm-submit" data-confirm-message="¿Eliminar producto?">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    <x-icons.trash />
                    <span>Eliminar</span>
                </button>
            </form>

            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Nombre</div>
                    <div class="summary-inline-value">{{ $product->name }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Precio</div>
                    <div class="summary-inline-value">
                        {{ $product->price !== null ? '$' . number_format((float) $product->price, 2, ',', '.') : '—' }}
                    </div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Unidad</div>
                    <div class="summary-inline-value">{{ $product->unit_label ?? '—' }}</div>
                </div>
            </div>

            <div class="list-filters-actions">
                <button type="button" class="btn btn-secondary" data-action="app-toggle-details"
                    data-toggle-target="#product-more-detail" data-toggle-text-collapsed="Más detalle"
                    data-toggle-text-expanded="Menos detalle">
                    Más detalle
                </button>
            </div>

            <div id="product-more-detail" hidden>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">Tipo</span>
                        <div class="detail-block-value">{{ ProductCatalog::label($product->kind) }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Activo</span>
                        <div class="detail-block-value">{{ $product->is_active ? 'Sí' : 'No' }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">SKU</span>
                        <div class="detail-block-value">{{ $product->sku ?? '—' }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Creado</span>
                        <div class="detail-block-value">{{ $product->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Actualizado</span>
                        <div class="detail-block-value">{{ $product->updated_at?->format('d/m/Y H:i') ?? '—' }}</div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Descripción</span>
                        <div class="detail-block-value">{{ $product->description ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </x-card>

    </x-page>
@endsection
