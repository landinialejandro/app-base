{{-- FILE: resources/views/documents/items/create.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Agregar ítem')

@section('content')
    @php
        $contextRouteParams = $navigationContext
            ? ['context_type' => $navigationContext['type'], 'context_id' => $navigationContext['id']]
            : [];

        $documentLabel = $document->number ?: 'Sin número';

        $breadcrumbItems = [['label' => 'Inicio', 'url' => route('dashboard')]];

        if (($navigationContext['type'] ?? null) === 'appointment' && $document->order) {
            $breadcrumbItems[] = ['label' => 'Turnos', 'url' => route('appointments.index')];
            $breadcrumbItems[] = ['label' => $navigationContext['label'], 'url' => $navigationContext['url']];
            $breadcrumbItems[] = [
                'label' => $document->order->number ?: 'Orden #' . $document->order->id,
                'url' => route('orders.show', ['order' => $document->order] + $contextRouteParams),
            ];
            $breadcrumbItems[] = [
                'label' => $documentLabel,
                'url' => route('documents.show', ['document' => $document] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Agregar ítem'];
        } else {
            $breadcrumbItems[] = ['label' => 'Documentos', 'url' => route('documents.index')];
            $breadcrumbItems[] = [
                'label' => $documentLabel,
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
