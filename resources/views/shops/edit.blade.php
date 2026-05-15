{{-- FILE: resources/views/shops/edit.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Editar tienda')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tiendas', 'url' => route('shops.index')],
            ['label' => $shop->name, 'url' => route('shops.show', $shop)],
            ['label' => 'Editar'],
        ]" />

        <x-page-header title="Editar tienda" />

        <x-card>
            <form method="POST" action="{{ route('shops.update', $shop) }}" class="form">
                @csrf
                @method('PUT')

                @include('shops._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('shops.show', $shop) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection