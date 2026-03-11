{{-- FILE: resources/views/documents/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar documento')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Documentos', 'url' => route('documents.index')],
            ['label' => $document->number ?: 'Sin número', 'url' => route('documents.show', $document)],
            ['label' => 'Editar'],
        ]" />

        <x-page-header title="Editar documento" />

        <x-card>
            <form method="POST" action="{{ route('documents.update', $document) }}" class="form">
                @csrf
                @method('PUT')

                @include('documents._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('documents.show', $document) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection