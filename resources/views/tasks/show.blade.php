{{-- solo reemplazá el bloque del header por este --}}

<x-page-header :title="$task->name">
    @if ($canEditTask)
        <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">
            <x-icons.pencil />
            <span>Editar</span>
        </a>
    @endif

    @if ($canDeleteTask)
        <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="inline-form"
            data-action="app-confirm-submit" data-confirm-message="¿Eliminar tarea?">
            @csrf
            @method('DELETE')

            <button type="submit" class="btn btn-danger">
                <x-icons.trash />
                <span>Eliminar</span>
            </button>
        </form>
    @endif

    @if (!$task->order && $task->party_id)
        <a href="{{ route('orders.create', ['task_id' => $task->id]) }}" class="btn btn-secondary">
            Crear orden
        </a>
    @endif

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
