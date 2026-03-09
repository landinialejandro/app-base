@extends('layouts.app')

@section('title', 'Tareas')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tareas'],
        ]" />

        <x-page-header title="Tareas">
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                Nueva tarea
            </a>
        </x-page-header>

        <x-card>
            @if ($tasks->isEmpty())
                <p class="mb-0">No hay tareas para esta empresa.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Proyecto</th>
                                <th>Contacto</th>
                                <th>Asignado a</th>
                                <th>Vencimiento</th>
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
                                    <td>{{ $task->status }}</td>
                                    <td>{{ $task->project?->name ?? '—' }}</td>
                                    <td>{{ $task->party?->name ?? '—' }}</td>
                                    <td>{{ $task->assignedUser?->name ?? '—' }}</td>
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