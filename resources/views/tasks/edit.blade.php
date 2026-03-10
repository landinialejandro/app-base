{{-- FILE: resources/views/tasks/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'Editar tarea')

@section('content')
    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar tarea">
            @if ($task->project)
                <a href="{{ route('projects.show', $task->project) }}" class="btn btn-secondary">
                    Volver al proyecto
                </a>
            @else
                <a href="{{ route('tasks.show', $task) }}" class="btn btn-secondary">
                    Volver al detalle
                </a>
            @endif
        </x-page-header>

        <x-card>
            <form method="POST" action="{{ route('tasks.update', $task) }}">
                @csrf
                @method('PUT')

                @include('tasks._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>

                    @if ($task->project)
                        <a href="{{ route('projects.show', $task->project) }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    @else
                        <a href="{{ route('tasks.show', $task) }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

    </x-page>
@endsection