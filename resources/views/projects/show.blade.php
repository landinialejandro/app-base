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

            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm(@js(
                $project->tasks->count()
                ? 'Este proyecto tiene tareas cargadas. Si lo eliminas, también se eliminarán sus tareas. ¿Deseas continuar?'
                : '¿Deseas eliminar este proyecto?'
            ));" class="inline-form">
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
                <div class="detail-label">ID</div>
                <div class="detail-value">{{ $project->id }}</div>

                <div class="detail-label">Nombre</div>
                <div class="detail-value">{{ $project->name }}</div>

                <div class="detail-label">Descripción</div>
                <div class="detail-value">{{ $project->description ?: '—' }}</div>

                <div class="detail-label">Tenant</div>
                <div class="detail-value">{{ $tenant->name }}</div>
            </div>
        </x-card>

        <x-card>
            <div class="card-header-actions">
                <h2 class="dashboard-section-title">Tareas del proyecto</h2>

                <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary">
                    Nueva tarea
                </a>
            </div>

            @if ($project->tasks->isEmpty())
                <p>No hay tareas asociadas a este proyecto.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Asignado a</th>
                                <th>Vencimiento</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($project->tasks as $task)
                                <tr>
                                    <td>
                                        <a href="{{ route('tasks.show', $task) }}">
                                            {{ $task->name }}
                                        </a>
                                    </td>

                                    <td>
                                        @php
                                            $statusMap = [
                                                'pending' => ['label' => 'Pendiente', 'class' => 'status-badge--pending'],
                                                'in_progress' => ['label' => 'En progreso', 'class' => 'status-badge--in-progress'],
                                                'done' => ['label' => 'Hecha', 'class' => 'status-badge--done'],
                                                'cancelled' => ['label' => 'Cancelada', 'class' => 'status-badge--cancelled'],
                                            ];

                                            $statusData = $statusMap[$task->status] ?? ['label' => $task->status, 'class' => ''];
                                        @endphp

                                        <span class="status-badge {{ $statusData['class'] }}">
                                            {{ $statusData['label'] }}
                                        </span>
                                    </td>

                                    <td>{{ $task->assignedUser?->name ?? 'Sin asignar' }}</td>

                                    <td>{{ $task->due_date?->format('d/m/Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

    </x-page>

@endsection