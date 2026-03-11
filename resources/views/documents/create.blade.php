{{-- FILE: resources/views/documents/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Nuevo documento')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Documentos', 'url' => route('documents.index')],
            ['label' => 'Nuevo documento'],
        ]" />

        <x-page-header title="Nuevo documento" />

        <x-card>
            <form method="POST" action="{{ route('documents.store') }}" class="form">
                @csrf

                @include('documents._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('documents.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection