{{-- FILE: resources/views/products/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Productos')

@section('content')

    @php
        use App\Support\Catalogs\ProductCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Productos']]" />

        <x-page-header title="Productos">
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                Nuevo producto
            </a>
        </x-page-header>

        <x-card class="list-card">

            <form method="GET" action="{{ route('products.index') }}" class="form list-filters">
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

                    <div class="form-group">
                        <label for="is_active" class="form-label">Activo</label>
                        <select id="is_active" name="is_active" class="form-control">
                            <option value="">Todos</option>
                            <option value="1" @selected(request('is_active') === '1')>Sí</option>
                            <option value="0" @selected(request('is_active') === '0')>No</option>
                        </select>
                    </div>
                </div>

                <div class="list-filters-actions">
                    <button type="submit" class="btn btn-primary">Filtrar</button>

                    <a href="{{ route('products.index') }}" class="btn btn-secondary">
                        Limpiar
                    </a>
                </div>
            </form>

            @if ($products->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>SKU</th>
                                <th>Precio</th>
                                <th>Unidad</th>
                                <th>Tipo</th>
                                <th>Activo</th>
                                <th>Creado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td>{{ $product->id }}</td>
                                    <td>
                                        <a href="{{ route('products.show', $product) }}">
                                            {{ $product->name }}
                                        </a>
                                    </td>
                                    <td>{{ $product->sku ?? '—' }}</td>
                                    <td>
                                        {{ $product->price !== null ? number_format((float) $product->price, 2, ',', '.') : '—' }}
                                    </td>
                                    <td>{{ $product->unit_label ?? '—' }}</td>
                                    <td>{{ ProductCatalog::label($product->kind) }}</td>
                                    <td>{{ $product->is_active ? 'Sí' : 'No' }}</td>
                                    <td>{{ $product->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{ $products->links() }}
                </div>
            @else
                <p class="mb-0">No hay productos para esta empresa.</p>
            @endif
        </x-card>

    </x-page>
@endsection
