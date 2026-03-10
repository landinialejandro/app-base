{{-- FILE: resources/views/products/index.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Productos')

@section('content')
    <x-page class="list-page">

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Productos'],
        ]" />

        <x-page-header title="Productos">
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                Nuevo producto
            </a>
        </x-page-header>

        <x-card class="list-card">
            @if ($products->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>SKU</th>
                                <th>Precio</th>
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
                                        {{ $product->price !== null
                                            ? number_format((float) $product->price, 2, ',', '.')
                                            : '—' }}
                                    </td>
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