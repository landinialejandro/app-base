{{-- FILE: resources/views/shops/items/edit.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Editar artículo')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tiendas', 'url' => route('shops.index')],
            ['label' => $shop->name, 'url' => route('shops.show', $shop)],
            ['label' => 'Editar artículo'],
        ]" />

        <x-page-header title="Editar artículo" />

        <x-card>
            <form method="POST" action="{{ route('shops.items.update', [$shop, $item]) }}" class="form">
                @csrf
                @method('PUT')

                @include('shops.items._form', [
                    'mode' => 'edit',
                ])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('shops.show', $shop) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection