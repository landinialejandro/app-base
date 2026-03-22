{{-- FILE: resources/views/orders/items/create.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Agregar ítem')

@section('content')
    @php
        $contextRouteParams = $navigationContext
            ? ['context_type' => $navigationContext['type'], 'context_id' => $navigationContext['id']]
            : [];

        $orderLabel = $order->number ?: 'Orden #' . $order->id;

        $breadcrumbItems = [['label' => 'Inicio', 'url' => route('dashboard')]];

        if (($navigationContext['type'] ?? null) === 'appointment') {
            $breadcrumbItems[] = ['label' => 'Turnos', 'url' => route('appointments.index')];
            $breadcrumbItems[] = ['label' => $navigationContext['label'], 'url' => $navigationContext['url']];
            $breadcrumbItems[] = [
                'label' => $orderLabel,
                'url' => route('orders.show', ['order' => $order] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Agregar ítem'];
        } else {
            $breadcrumbItems[] = ['label' => 'Órdenes', 'url' => route('orders.index')];
            $breadcrumbItems[] = [
                'label' => $orderLabel,
                'url' => route('orders.show', ['order' => $order] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Agregar ítem'];
        }

        $cancelUrl = route('orders.show', ['order' => $order] + $contextRouteParams);
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Agregar ítem" />

        <x-card>
            <form method="POST" action="{{ route('orders.items.store', ['order' => $order] + $contextRouteParams) }}"
                class="form">
                @csrf

                @include('orders.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
