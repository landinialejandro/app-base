{{-- FILE: resources/views/products/index.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Productos')

@section('content')

    @php
        use App\Support\Catalogs\ProductCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Productos']]" />

        <x-page-header title="Productos">
            @can('create', App\Models\Product::class)
                <a href="{{ route('products.create') }}" class="btn btn-primary">
                    Nuevo producto
                </a>
            @endcan
        </x-page-header>

        <x-list-filters-card :action="route('products.index')" secondary-id="products-extra-filters">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre, SKU o ID">
                    </div>

                    <div class="form-group">
                        <label for="kind" class="form-label">Tipo</label>
                        <select id="kind" name="kind" class="form-control">
                            <option value="">Todos</option>
                            @foreach (ProductCatalog::kindLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('kind') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-slot:primary>

            <x-slot:secondary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="is_active" class="form-label">Activo</label>
                        <select id="is_active" name="is_active" class="form-control">
                            <option value="">Todos</option>
                            <option value="1" @selected(request('is_active') === '1')>Sí</option>
                            <option value="0" @selected(request('is_active') === '0')>No</option>
                        </select>
                    </div>
                </div>
            </x-slot:secondary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('products.partials.table', [
                'products' => $products,
                'emptyMessage' => 'No hay productos para esta empresa.',
            ])

            @if ($products->count())
                {{ $products->links() }}
            @endif
        </x-card>

    </x-page>
@endsection
