{{-- FILE: resources/views/tasks/index.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Tareas')

@section('content')

    @php
        use App\Support\Catalogs\TaskCatalog;
    @endphp

    <x-page class="list-page">
        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Tareas']]" />

        <x-page-header title="Tareas">
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                Nueva tarea
            </a>
        </x-page-header>

        <x-card class="list-card">

            <form method="GET" action="{{ route('tasks.index') }}" class="form list-filters">
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre o ID">
                    </div>

                    <div class="form-group">
                        <label for="project_id" class="form-label">Proyecto</label>
                        <select id="project_id" name="project_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach (TaskCatalog::statusLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assigned_user_id" class="form-label">Asignado a</label>
                        <select id="assigned_user_id" name="assigned_user_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_user_id') === (string) $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="list-filters-actions">
                    <button type="submit" class="btn btn-primary">Filtrar</button>

                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                        Limpiar
                    </a>
                </div>
            </form>

            @if ($tasks->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Proyecto</th>
                                <th>Estado</th>
                                <th>Asignado a</th>
                                <th>Vencimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks as $task)
                                <tr>
                                    <td>
                                        <a href="{{ route('tasks.show', $task) }}">
                                            {{ $task->name }}
                                        </a>
                                    </td>

                                    <td>
                                        @if ($task->project)
                                            <a href="{{ route('projects.show', $task->project) }}">
                                                {{ $task->project->name }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td>
                                        <span class="status-badge {{ TaskCatalog::badgeClass($task->status) }}">
                                            {{ TaskCatalog::label($task->status) }}
                                        </span>
                                    </td>

                                    <td>{{ $task->assignedUser?->name ?? 'Sin asignar' }}</td>
                                    <td>{{ $task->due_date?->format('d/m/Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{ $tasks->links() }}
                </div>
            @else
                <p class="mb-0">No hay tareas registradas para esta empresa.</p>
            @endif
        </x-card>
    </x-page>
@endsection
