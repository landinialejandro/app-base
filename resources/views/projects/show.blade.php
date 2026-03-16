{{-- FILE: resources/views/projects/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle del proyecto')

@section('content')
    @php
        use App\Support\Catalogs\TaskCatalog;

        $tasks = $project->tasks;
        $pendingCount = $tasks->where('status', 'pending')->count();
        $inProgressCount = $tasks->where('status', 'in_progress')->count();
        $doneCount = $tasks->where('status', 'done')->count();
        $cancelledCount = $tasks->where('status', 'cancelled')->count();
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Proyectos', 'url' => route('projects.index')],
            ['label' => $project->name],
        ]" />

        <x-page-header title="Detalle del proyecto">
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">
                <x-icons.pencil />
                <span>Editar</span>
            </a>

            <form method="POST" action="{{ route('projects.destroy', $project) }}" class="inline-form"
                data-action="app-confirm-submit"
                data-confirm-message="{{ $project->tasks->count()
                    ? 'Este proyecto tiene tareas asociadas. Si lo eliminas, también se eliminarán sus tareas. ¿Deseas continuar?'
                    : '¿Deseas eliminar este proyecto?' }}">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    <x-icons.trash />
                    <span>Eliminar</span>
                </button>
            </form>

            <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Nombre</div>
                    <div class="summary-inline-value">{{ $project->name }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Tareas</div>
                    <div class="summary-inline-value">{{ $tasks->count() }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="detail-grid detail-grid--3">
                <div class="detail-block">
                    <span class="detail-block-label">Pendientes</span>
                    <div class="detail-block-value">{{ $pendingCount }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">En curso</span>
                    <div class="detail-block-value">{{ $inProgressCount }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Finalizadas</span>
                    <div class="detail-block-value">{{ $doneCount }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Canceladas</span>
                    <div class="detail-block-value">{{ $cancelledCount }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Creado</span>
                    <div class="detail-block-value">{{ $project->created_at?->format('d/m/Y H:i') ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Actualizado</span>
                    <div class="detail-block-value">{{ $project->updated_at?->format('d/m/Y H:i') ?: '—' }}</div>
                </div>

                <div class="detail-block detail-block--full">
                    <span class="detail-block-label">Descripción</span>
                    <div class="detail-block-value">{{ $project->description ?: '—' }}</div>
                </div>
            </div>
        </x-card>

        <x-page-header title="Tareas del proyecto">
            <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary">
                Agregar tarea
            </a>
        </x-page-header>

        <x-card class="list-card">
            @if ($tasks->count())
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
                            @foreach ($tasks as $task)
                                <tr>
                                    <td>{{ $task->id }}</td>
                                    <td>
                                        <a href="{{ route('tasks.show', $task) }}">
                                            {{ $task->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ TaskCatalog::badgeClass($task->status) }}">
                                            {{ TaskCatalog::label($task->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $task->assignedUser?->name ?: '—' }}</td>
                                    <td>{{ $task->due_date ? \Illuminate\Support\Carbon::parse($task->due_date)->format('d/m/Y') : '—' }}
                                    </td>
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
