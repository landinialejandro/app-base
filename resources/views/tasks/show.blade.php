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

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Asignado a</div>
                    <div class="summary-inline-value">{{ $task->assignedUser?->name ?? 'Sin asignar' }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Prioridad</div>
                    <div class="summary-inline-value">
                        <span class="status-badge {{ TaskCatalog::priorityBadgeClass($task->priority) }}">
                            {{ TaskCatalog::priorityLabel($task->priority) }}
                        </span>
                    </div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Estado</div>
                    <div class="summary-inline-value">
                        <span class="status-badge {{ TaskCatalog::badgeClass($task->status) }}">
                            {{ TaskCatalog::label($task->status) }}
                        </span>
                    </div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Vencimiento</div>
                    <div class="summary-inline-value">{{ $task->due_date?->format('d/m/Y') ?? '—' }}</div>
                    <div class="summary-inline-help">{{ $dueDateText }}</div>
                </div>
            </div>

            <div class="list-filters-actions">
                <button type="button" class="btn btn-secondary" data-action="app-toggle-details"
                    data-toggle-target="#task-more-detail" data-toggle-text-collapsed="Más detalle"
                    data-toggle-text-expanded="Menos detalle">
                    Más detalle
                </button>
            </div>

            <div id="task-more-detail" hidden>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">Proyecto</span>
                        <div class="detail-block-value">
                            @if ($task->project)
                                <a href="{{ route('projects.show', $task->project) }}">
                                    {{ $task->project->name }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
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
            </div>
        </x-card>

    </x-page>
@endsection
