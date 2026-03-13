{{-- FILE: resources/views/products/create.blade.php --}}

@extends('layouts.app')

@section('title', 'Nuevo producto')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Productos', 'url' => route('products.index')],
            ['label' => 'Nuevo producto'],
        ]" />

        <x-page-header title="Nuevo producto" />

        <x-card>
            <form action="{{ route('products.store') }}" method="POST" class="form">
                @include('products._form')
            </form>
        </x-card>

    </x-page>
@endsection