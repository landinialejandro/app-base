@extends('layouts.app')

@section('title', $task->name)

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tareas', 'url' => route('tasks.index')],
            ['label' => $task->name],
        ]" />

        <x-page-header :title="$task->name">
            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">
                Editar
            </a>
        </x-page-header>

        <x-card>
            <p><strong>ID:</strong> {{ $task->id }}</p>
            <p><strong>Nombre:</strong> {{ $task->name }}</p>
            <p><strong>Descripción:</strong> {{ $task->description ?: '—' }}</p>
            <p><strong>Estado:</strong> {{ $task->status }}</p>
            <p><strong>Proyecto:</strong> {{ $task->project?->name ?? '—' }}</p>
            <p><strong>Contacto:</strong> {{ $task->party?->name ?? '—' }}</p>
            <p><strong>Asignado a:</strong> {{ $task->assignedUser?->name ?? '—' }}</p>
            <p><strong>Vencimiento:</strong> {{ $task->due_date?->format('d/m/Y') ?? '—' }}</p>

            <hr>

            <div class="form-actions">
                <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                    Volver al listado
                </a>

                <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                    onsubmit="return confirm('¿Eliminar tarea?');" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        Eliminar
                    </button>
                </form>
            </div>
        </x-card>

    </x-page>
@endsection