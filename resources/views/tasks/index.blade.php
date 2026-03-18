{{-- FILE: resources/views/tasks/index.blade.php |V7 --}}

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

        <x-list-filters-card :action="route('tasks.index')" secondary-id="tasks-extra-filters">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre o ID">
                    </div>

                    <div class="form-group">
                        <label for="scope" class="form-label">Vista</label>
                        <select id="scope" name="scope" class="form-control">
                            <option value="mine" @selected(($scope ?? request('scope', 'mine')) === 'mine')>Mis tareas</option>
                            <option value="all" @selected(($scope ?? request('scope', 'mine')) === 'all')>Todas las tareas</option>
                        </select>
                    </div>
                </div>
            </x-slot:primary>

            <x-slot:secondary>
                <div class="list-filters-grid">
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
                        <label for="priority" class="form-label">Prioridad</label>
                        <select id="priority" name="priority" class="form-control">
                            <option value="">Todas</option>
                            @foreach (TaskCatalog::priorityLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('priority') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
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
            </x-slot:secondary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('tasks.partials.table', [
                'tasks' => $tasks,
                'emptyMessage' => 'No hay tareas registradas para esta empresa.',
            ])

            @if ($tasks->count())
                {{ $tasks->links() }}
            @endif
        </x-card>
    </x-page>
@endsection
