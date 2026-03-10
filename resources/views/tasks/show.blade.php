{{-- FILE: resources/views/tasks/show.blade.php --}}

@extends('layouts.app')

@section('title', $task->name)

@section('content')
    <x-page>

        <x-breadcrumb :items="$task->project
            ? [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Proyectos', 'url' => route('projects.index')],
                ['label' => $task->project->name, 'url' => route('projects.show', $task->project)],
                ['label' => $task->name],
            ]
            : [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Tareas', 'url' => route('tasks.index')],
                ['label' => $task->name],
            ]" />

        <x-page-header :title="$task->name">
            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">
                Editar
            </a>

            <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                onsubmit="return confirm('¿Eliminar tarea?');" class="inline-form">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Eliminar
                </button>
            </form>

            @if ($task->project)
                <a href="{{ route('projects.show', $task->project) }}" class="btn btn-secondary">
                    Volver al proyecto
                </a>
            @else
                <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                    Volver al listado
                </a>
            @endif
        </x-page-header>

        <x-card>
            <div class="detail-list">
                <div class="detail-label">ID</div>
                <div class="detail-value">{{ $task->id }}</div>

                <div class="detail-label">Nombre</div>
                <div class="detail-value">{{ $task->name }}</div>

                <div class="detail-label">Descripción</div>
                <div class="detail-value">{{ $task->description ?: '—' }}</div>

                <div class="detail-label">Estado</div>
                <div class="detail-value">{{ $task->status }}</div>

                <div class="detail-label">Proyecto</div>
                <div class="detail-value">
                    @if ($task->project)
                        <a href="{{ route('projects.show', $task->project) }}">
                            {{ $task->project->name }}
                        </a>
                    @else
                        —
                    @endif
                </div>

                <div class="detail-label">Contacto</div>
                <div class="detail-value">{{ $task->party?->name ?? '—' }}</div>

                <div class="detail-label">Asignado a</div>
                <div class="detail-value">{{ $task->assignedUser?->name ?? '—' }}</div>

                <div class="detail-label">Vencimiento</div>
                <div class="detail-value">{{ $task->due_date?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </x-card>

    </x-page>
@endsection