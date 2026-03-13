{{-- FILE: resources/views/projects/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle del proyecto')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Proyectos', 'url' => route('projects.index')],
            ['label' => $project->name],
        ]" />

        <x-page-header title="Detalle del proyecto">
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">
                Editar
            </a>

            <form method="POST" action="{{ route('projects.destroy', $project) }}" class="d-inline"
                onsubmit="return confirm(@js(
                    $project->tasks->count()
                        ? 'Este proyecto tiene tareas asociadas. Si lo eliminas, también se eliminarán sus tareas. ¿Deseas continuar?'
                        : '¿Deseas eliminar este proyecto?'
                ))">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Eliminar
                </button>
            </form>

            <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="detail-list">
                <div>
                    <div class="detail-label">ID</div>
                    <div class="detail-value">{{ $project->id }}</div>
                </div>

                <div>
                    <div class="detail-label">Nombre</div>
                    <div class="detail-value">{{ $project->name }}</div>
                </div>

                <div>
                    <div class="detail-label">Descripción</div>
                    <div class="detail-value">{{ $project->description ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Cantidad de tareas</div>
                    <div class="detail-value">{{ $project->tasks->count() }}</div>
                </div>

                <div>
                    <div class="detail-label">Creado</div>
                    <div class="detail-value">{{ $project->created_at?->format('d/m/Y H:i') ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Actualizado</div>
                    <div class="detail-value">{{ $project->updated_at?->format('d/m/Y H:i') ?: '—' }}</div>
                </div>
            </div>
        </x-card>

        <x-page-header title="Tareas del proyecto">
            <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary">
                Agregar tarea
            </a>
        </x-page-header>

        <x-card class="list-card">
            @if ($project->tasks->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Asignado a</th>
                                <th>Vence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($project->tasks as $task)
                                <tr>
                                    <td>{{ $task->id }}</td>
                                    <td>
                                        <a href="{{ route('tasks.show', $task) }}">
                                            {{ $task->name }}
                                        </a>
                                    </td>
                                    <td>{{ $task->status }}</td>
                                    <td>{{ $task->assignedUser?->name ?: '—' }}</td>
                                    <td>{{ $task->due_date ? \Illuminate\Support\Carbon::parse($task->due_date)->format('d/m/Y') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0">No hay tareas asociadas a este proyecto.</p>
            @endif
        </x-card>

    </x-page>
@endsection
