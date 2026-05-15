{{-- FILE: resources/views/shops/items/create.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Agregar artículo')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tiendas', 'url' => route('shops.index')],
            ['label' => $shop->name, 'url' => route('shops.show', $shop)],
            ['label' => 'Agregar artículo'],
        ]" />

        <x-page-header title="Agregar artículo" />

        <x-card>
            <form method="POST" action="{{ route('shops.items.store', $shop) }}" class="form">
                @csrf

                @include('shops.items._form', [
                    'mode' => 'create',
                ])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('shops.show', $shop) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection