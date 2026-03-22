{{-- FILE: resources/views/documents/items/create.blade.php | V 3 --}}
@extends('layouts.app')

@section('title', 'Agregar ítem')

@section('content')
    @php
        $contextRouteParams = request()->only(['context_type', 'context_id']);

        $breadcrumbItems = [['label' => 'Inicio', 'url' => route('dashboard')]];

        if (($contextRouteParams['context_type'] ?? null) === 'appointment' && $document->order) {
            $breadcrumbItems[] = ['label' => 'Turnos', 'url' => route('appointments.index')];
            $breadcrumbItems[] = ['label' => 'Turno', 'url' => url()->previous()];
            $breadcrumbItems[] = [
                'label' => $document->order->number ?: 'Orden #' . $document->order->id,
                'url' => route('orders.show', ['order' => $document->order] + $contextRouteParams),
            ];
            $breadcrumbItems[] = [
                'label' => $document->number ?: 'Sin número',
                'url' => route('documents.show', ['document' => $document] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Agregar ítem'];
        } else {
            $breadcrumbItems[] = ['label' => 'Documentos', 'url' => route('documents.index')];
            $breadcrumbItems[] = [
                'label' => $document->number ?: 'Sin número',
                'url' => route('documents.show', ['document' => $document] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Agregar ítem'];
        }
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Agregar ítem" />

        <x-card>
            <form method="POST"
                action="{{ route('documents.items.store', ['document' => $document] + $contextRouteParams) }}" class="form">
                @csrf

                @include('documents.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('documents.show', ['document' => $document] + $contextRouteParams) }}"
                        class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection
