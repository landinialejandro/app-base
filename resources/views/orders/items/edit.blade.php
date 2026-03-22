{{-- FILE: resources/views/orders/items/edit.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Editar ítem')

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
            $breadcrumbItems[] = ['label' => 'Editar ítem'];
        } else {
            $breadcrumbItems[] = ['label' => 'Órdenes', 'url' => route('orders.index')];
            $breadcrumbItems[] = ['label' => $orderLabel, 'url' => route('orders.show', $order)];
            $breadcrumbItems[] = ['label' => 'Editar ítem'];
        }

        $cancelUrl = route('orders.show', ['order' => $order] + $contextRouteParams);
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar ítem" />

        <x-card>
            <form method="POST"
                action="{{ route('orders.items.update', ['order' => $order, 'item' => $item] + $contextRouteParams) }}"
                class="form">
                @csrf
                @method('PUT')

                @include('orders.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection
