@extends('layouts.app')

@section('title', 'Editar tarea')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tareas', 'url' => route('tasks.index')],
            ['label' => $task->name, 'url' => route('tasks.show', $task)],
            ['label' => 'Editar'],
        ]" />

        <x-page-header title="Editar tarea" />

        <x-card>
            <form method="POST" action="{{ route('tasks.update', $task) }}">
                @csrf
                @method('PUT')

                @include('tasks._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('tasks.show', $task) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection