{{-- FILE: resources/views/orders/edit.blade.php | V2  --}}

@extends('layouts.app')

@section('title', 'Editar orden')

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
            $breadcrumbItems[] = ['label' => 'Editar'];
        } else {
            $breadcrumbItems[] = ['label' => 'Órdenes', 'url' => route('orders.index')];
            $breadcrumbItems[] = ['label' => $orderLabel, 'url' => route('orders.show', $order)];
            $breadcrumbItems[] = ['label' => 'Editar'];
        }

        $cancelUrl = route('orders.show', ['order' => $order] + $contextRouteParams);
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar orden" />

        <x-card>
            <form method="POST" action="{{ route('orders.update', ['order' => $order] + $contextRouteParams) }}"
                class="form">
                @csrf
                @method('PUT')

                @include('orders._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection
