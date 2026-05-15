{{-- FILE: resources/views/shops/create.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Nueva tienda')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tiendas', 'url' => route('shops.index')],
            ['label' => 'Nueva tienda'],
        ]" />

        <x-page-header title="Nueva tienda" />

        <x-card>
            <form method="POST" action="{{ route('shops.store') }}" class="form">
                @csrf

                @include('shops._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('shops.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection