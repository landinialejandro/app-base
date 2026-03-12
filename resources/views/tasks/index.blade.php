{{-- FILE: resources/views/tasks/index.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Tareas')

@section('content')

    @php
        use App\Support\Catalogs\TaskCatalog;
    @endphp

    <x-page class="list-page">
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tareas'],
        ]" />

        <x-page-header title="Tareas">
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                Nueva tarea
            </a>
        </x-page-header>

        <x-card class="list-card">
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
                </div>
            @else
                <p class="mb-0">No hay tareas registradas para esta empresa.</p>
            @endif
        </x-card>
    </x-page>
@endsection
