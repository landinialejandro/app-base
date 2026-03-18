{{-- FILE: resources/views/tasks/show.blade.php |V4 --}}

@extends('layouts.app')

@section('title', $task->name)

@section('content')
    @php
        use App\Support\Catalogs\TaskCatalog;
        use Illuminate\Support\Carbon;

        $dueDateText = 'Sin vencimiento';

        if ($task->due_date) {
            $today = now()->startOfDay();
            $dueDate = Carbon::parse($task->due_date)->startOfDay();
            $diffInDays = $today->diffInDays($dueDate, false);

            $dueDateText = match (true) {
                $diffInDays === 0 => 'Vence hoy',
                $diffInDays === 1 => 'Vence mañana',
                $diffInDays === -1 => 'Venció ayer',
                $diffInDays > 1 => "Vence en {$diffInDays} días",
                $diffInDays < -1 => 'Venció hace ' . abs($diffInDays) . ' días',
                default => 'Sin vencimiento',
            };
        }
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

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

        <x-show-summary details-id="task-more-detail">
            <x-show-summary-item label="Asignado a">
                {{ $task->assignedUser?->name ?? 'Sin asignar' }}
            </x-show-summary-item>

            <x-show-summary-item label="Proyecto">
                @if ($task->project)
                    <a href="{{ route('projects.show', $task->project) }}">
                        {{ $task->project->name }}
                    </a>
                @else
                    —
                @endif
            </x-show-summary-item>

            <x-show-summary-item label="Estado operativo" help="{{ $dueDateText }}">
                <div class="summary-badge-stack">
                    <span class="status-badge {{ TaskCatalog::priorityBadgeClass($task->priority) }}">
                        Prioridad: {{ TaskCatalog::priorityLabel($task->priority) }}
                    </span>

                    <span class="status-badge {{ TaskCatalog::badgeClass($task->status) }}">
                        Estado: {{ TaskCatalog::label($task->status) }}
                    </span>
                </div>
            </x-show-summary-item>

            <x-slot:details>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">Vencimiento</span>
                        <div class="detail-block-value">{{ $task->due_date?->format('d/m/Y') ?? 'Sin fecha' }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Contacto</span>
                        <div class="detail-block-value">{{ $task->party?->name ?? '—' }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Orden asociada</span>
                        <div class="detail-block-value">
                            @if ($task->order)
                                <a href="{{ route('orders.show', $task->order) }}">
                                    {{ $task->order->number ?: 'Ver orden' }}
                                </a>
                            @elseif ($task->party_id)
                                <a href="{{ route('orders.create', ['task_id' => $task->id]) }}">
                                    Crear orden
                                </a>
                            @else
                                Asociá un contacto para poder crear una orden.
                            @endif
                        </div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Descripción</span>
                        <div class="detail-block-value">{{ $task->description ?: '—' }}</div>
                    </div>
                </div>
            </x-slot:details>
        </x-show-summary>

    </x-page>
@endsection
