{{-- FILE: resources/views/documents/create.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Nuevo documento')

@section('content')
    @php
        $contextRouteParams = $navigationContext
            ? ['context_type' => $navigationContext['type'], 'context_id' => $navigationContext['id']]
            : [];

        $breadcrumbItems = [['label' => 'Inicio', 'url' => route('dashboard')]];

        if (($navigationContext['type'] ?? null) === 'appointment' && isset($order) && $order) {
            $breadcrumbItems[] = ['label' => 'Turnos', 'url' => route('appointments.index')];
            $breadcrumbItems[] = ['label' => $navigationContext['label'], 'url' => $navigationContext['url']];
            $breadcrumbItems[] = [
                'label' => $order->number ?: 'Orden #' . $order->id,
                'url' => route('orders.show', ['order' => $order] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Nuevo documento'];
        } elseif (isset($order) && $order) {
            $breadcrumbItems[] = ['label' => 'Órdenes', 'url' => route('orders.index')];
            $breadcrumbItems[] = [
                'label' => $order->number ?: 'Orden #' . $order->id,
                'url' => route('orders.show', ['order' => $order] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Nuevo documento'];
        } else {
            $breadcrumbItems[] = ['label' => 'Documentos', 'url' => route('documents.index')];
            $breadcrumbItems[] = ['label' => 'Nuevo documento'];
        }

        $cancelUrl =
            isset($order) && $order
                ? route('orders.show', ['order' => $order] + $contextRouteParams)
                : route('documents.index');
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Nuevo documento" />

        <x-card>
            <form method="POST" action="{{ route('documents.store', $contextRouteParams) }}" class="form">
                @csrf

                @include('documents._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection
