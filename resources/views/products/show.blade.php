{{-- FILE: resources/views/products/show.blade.php --}}
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
                Editar
            </a>

            <form method="POST" action="{{ route('products.destroy', $product) }}"
                onsubmit="return confirm('¿Eliminar producto?');" class="inline-form">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Eliminar
                </button>
            </form>

            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Tipo</div>
                    <div class="summary-inline-value">{{ ProductCatalog::label($product->kind) }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Nombre</div>
                    <div class="summary-inline-value">{{ $product->name }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="detail-grid detail-grid--3">
                <div class="detail-block">
                    <span class="detail-block-label">SKU</span>
                    <div class="detail-block-value">{{ $product->sku ?? '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Precio</span>
                    <div class="detail-block-value">
                        {{ $product->price !== null ? '$' . number_format((float) $product->price, 2, ',', '.') : '—' }}
                    </div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Unidad</span>
                    <div class="detail-block-value">{{ $product->unit_label ?? '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Activo</span>
                    <div class="detail-block-value">{{ $product->is_active ? 'Sí' : 'No' }}</div>
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
                    <div class="detail-block-value">{{ $product->description ?? '—' }}</div>
                </div>
            </div>
        </x-card>

    </x-page>
@endsection