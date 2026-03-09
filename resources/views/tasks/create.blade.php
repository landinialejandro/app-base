@extends('layouts.app')

@section('title', 'Nueva tarea')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tareas', 'url' => route('tasks.index')],
            ['label' => 'Nueva tarea'],
        ]" />

        <x-page-header title="Nueva tarea" />

        <x-card>
            <form method="POST" action="{{ route('tasks.store') }}">
                @csrf

                @include('tasks._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection