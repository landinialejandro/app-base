@extends('layouts.app')

@section('title', 'Detalle del producto')

@section('content')
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
            <div class="detail-list">
                <div class="detail-label">ID</div>
                <div class="detail-value">{{ $product->id }}</div>

                <div class="detail-label">Nombre</div>
                <div class="detail-value">{{ $product->name }}</div>

                <div class="detail-label">SKU</div>
                <div class="detail-value">{{ $product->sku ?? '—' }}</div>

                <div class="detail-label">Descripción</div>
                <div class="detail-value">{{ $product->description ?? '—' }}</div>

                <div class="detail-label">Precio</div>
                <div class="detail-value">
                    {{ $product->price !== null ? number_format((float) $product->price, 2, ',', '.') : '—' }}
                </div>

                <div class="detail-label">Activo</div>
                <div class="detail-value">{{ $product->is_active ? 'Sí' : 'No' }}</div>

                <div class="detail-label">Creado</div>
                <div class="detail-value">{{ $product->created_at }}</div>

                <div class="detail-label">Actualizado</div>
                <div class="detail-value">{{ $product->updated_at }}</div>
            </div>
        </x-card>

    </x-page>
@endsection