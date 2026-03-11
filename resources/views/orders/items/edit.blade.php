@extends('layouts.app')

@section('title', 'Editar ítem')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Órdenes', 'url' => route('orders.index')],
            ['label' => $order->number ?: 'Sin número', 'url' => route('orders.show', $order)],
            ['label' => 'Editar ítem'],
        ]" />

        <x-page-header title="Editar ítem" />

        <x-card>
            <form method="POST" action="{{ route('orders.items.update', [$order, $item]) }}" class="form">
                @csrf
                @method('PUT')

                @include('orders.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection