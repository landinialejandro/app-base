{{-- FILE: resources/views/products/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'Editar producto')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Productos', 'url' => route('products.index')],
            ['label' => $product->name, 'url' => route('products.show', $product)],
            ['label' => 'Editar'],
        ]" />

        <x-page-header title="Editar producto" />

        <x-card>
            <form action="{{ route('products.update', $product) }}" method="POST" class="form">
                @method('PUT')
                @include('products._form', ['product' => $product])
            </form>
        </x-card>

    </x-page>
@endsection