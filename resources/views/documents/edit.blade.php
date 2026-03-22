{{-- FILE: resources/views/documents/edit.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Editar documento')

@section('content')
    @php
        $contextRouteParams = $navigationContext
            ? ['context_type' => $navigationContext['type'], 'context_id' => $navigationContext['id']]
            : [];

        $documentLabel = $document->number ?: 'Documento #' . $document->id;
        $orderLabel = $document->order ? ($document->order->number ?: 'Orden #' . $document->order->id) : null;

        $breadcrumbItems = [['label' => 'Inicio', 'url' => route('dashboard')]];

        if (($navigationContext['type'] ?? null) === 'appointment' && $document->order) {
            $breadcrumbItems[] = ['label' => 'Turnos', 'url' => route('appointments.index')];
            $breadcrumbItems[] = ['label' => $navigationContext['label'], 'url' => $navigationContext['url']];
            $breadcrumbItems[] = [
                'label' => $orderLabel,
                'url' => route('orders.show', ['order' => $document->order] + $contextRouteParams),
            ];
            $breadcrumbItems[] = [
                'label' => $documentLabel,
                'url' => route('documents.show', ['document' => $document] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Editar'];
        } elseif ($document->order) {
            $breadcrumbItems[] = ['label' => 'Órdenes', 'url' => route('orders.index')];
            $breadcrumbItems[] = [
                'label' => $orderLabel,
                'url' => route('orders.show', ['order' => $document->order] + $contextRouteParams),
            ];
            $breadcrumbItems[] = [
                'label' => $documentLabel,
                'url' => route('documents.show', ['document' => $document] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Editar'];
        } else {
            $breadcrumbItems[] = ['label' => 'Documentos', 'url' => route('documents.index')];
            $breadcrumbItems[] = [
                'label' => $documentLabel,
                'url' => route('documents.show', ['document' => $document] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Editar'];
        }

        $cancelUrl = route('documents.show', ['document' => $document] + $contextRouteParams);
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar documento" />

        <x-card>
            <form method="POST" action="{{ route('documents.update', ['document' => $document] + $contextRouteParams) }}"
                class="form">
                @csrf
                @method('PUT')

                @include('documents._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
