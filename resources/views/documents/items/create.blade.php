{{-- FILE: resources/views/documents/items/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Agregar ítem')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Documentos', 'url' => route('documents.index')],
            ['label' => $document->number ?: 'Sin número', 'url' => route('documents.show', $document)],
            ['label' => 'Agregar ítem'],
        ]" />

        <x-page-header title="Agregar ítem" />

        <x-card>
            <form method="POST" action="{{ route('documents.items.store', $document) }}" class="form">
                @csrf

                @include('documents.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('documents.show', $document) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection