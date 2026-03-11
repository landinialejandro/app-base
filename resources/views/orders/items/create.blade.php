@extends('layouts.app')

@section('title', 'Agregar ítem')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Órdenes', 'url' => route('orders.index')],
            ['label' => $order->number ?: 'Sin número', 'url' => route('orders.show', $order)],
            ['label' => 'Agregar ítem'],
        ]" />

        <x-page-header title="Agregar ítem" />

        <x-card>
            <form method="POST" action="{{ route('orders.items.store', $order) }}" class="form">
                @csrf

                @include('orders.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection