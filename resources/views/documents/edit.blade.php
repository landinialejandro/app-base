{{-- FILE: resources/views/documents/edit.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Editar documento')

@section('content')
    @php
        $contextRouteParams = $navigationContext
            ? ['context_type' => $navigationContext['type'], 'context_id' => $navigationContext['id']]
            : [];

        $documentLabel = $document->number ?: 'Sin número';

        $breadcrumbItems = [['label' => 'Inicio', 'url' => route('dashboard')]];

        if (($navigationContext['type'] ?? null) === 'appointment') {
            $breadcrumbItems[] = ['label' => 'Turnos', 'url' => route('appointments.index')];
            $breadcrumbItems[] = ['label' => $navigationContext['label'], 'url' => $navigationContext['url']];

            if ($document->order) {
                $breadcrumbItems[] = [
                    'label' => $document->order->number ?: 'Orden #' . $document->order->id,
                    'url' => route('orders.show', ['order' => $document->order] + $contextRouteParams),
                ];
            }

            $breadcrumbItems[] = [
                'label' => $documentLabel,
                'url' => route('documents.show', ['document' => $document] + $contextRouteParams),
            ];
            $breadcrumbItems[] = ['label' => 'Editar'];
        } else {
            $breadcrumbItems[] = ['label' => 'Documentos', 'url' => route('documents.index')];
            $breadcrumbItems[] = ['label' => $documentLabel, 'url' => route('documents.show', $document)];
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
