{{-- FILE: resources/views/projects/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle del proyecto')

@section('content')
    @php
        use App\Support\Catalogs\ProjectCatalog;
        $membership = auth()
            ->user()
            ?->memberships()
            ->with('roles')
            ->where('tenant_id', app('tenant')->id)
            ->where('status', 'active')
            ->first();

        $canDeleteProject =
            $membership &&
            ($membership->is_owner || $membership->roles->contains(fn($role) => $role->slug === 'admin'));

        $tasks = $project->tasks->sortBy([['due_date', 'asc'], ['name', 'asc']])->values();

        $openTasks = $tasks->whereIn('status', ['pending', 'in_progress'])->values();
        $doneTasks = $tasks->where('status', 'done')->values();
        $cancelledTasks = $tasks->where('status', 'cancelled')->values();

        $pendingCount = $tasks->where('status', 'pending')->count();
        $inProgressCount = $tasks->where('status', 'in_progress')->count();
        $doneCount = $doneTasks->count();
        $cancelledCount = $cancelledTasks->count();

        $overdueCount = $tasks
            ->filter(function ($task) {
                return $task->due_date &&
                    $task->due_date->isPast() &&
                    !in_array($task->status, ['done', 'cancelled'], true);
            })
            ->count();

        $progress = $tasks->count() > 0 ? round(($doneCount / $tasks->count()) * 100) : 0;
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

            @if ($canDeleteProject)
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
            @endif

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

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Estado</div>
                    <div class="summary-inline-value">
                        <span class="status-badge {{ ProjectCatalog::badgeClass($project->status) }}">
                            {{ ProjectCatalog::label($project->status) }}
                        </span>
                    </div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Abiertas</div>
                    <div class="summary-inline-value">{{ $openTasks->count() }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Avance</div>
                    <div class="summary-inline-value">{{ $progress }}%</div>
                </div>
            </div>

            <div class="list-filters-actions">
                <button type="button" class="btn btn-secondary" data-action="app-toggle-details"
                    data-toggle-target="#project-more-detail" data-toggle-text-collapsed="Más detalle"
                    data-toggle-text-expanded="Menos detalle">
                    Más detalle
                </button>
            </div>

            <div id="project-more-detail" hidden>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">Pendientes</span>
                        <div class="detail-block-value">{{ $pendingCount }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">En progreso</span>
                        <div class="detail-block-value">{{ $inProgressCount }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Vencidas</span>
                        <div class="detail-block-value">{{ $overdueCount }}</div>
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
                        <span class="detail-block-label">Actualizado</span>
                        <div class="detail-block-value">{{ $project->updated_at?->format('d/m/Y H:i') ?: '—' }}</div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Descripción</span>
                        <div class="detail-block-value">{{ $project->description ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </x-card>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Tareas del proyecto">
                <button type="button" class="tabs-link is-active" data-tab-link="open" role="tab" aria-selected="true">
                    Abiertas @if ($openTasks->count())
                        ({{ $openTasks->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link" data-tab-link="done" role="tab" aria-selected="false">
                    Finalizadas @if ($doneTasks->count())
                        ({{ $doneTasks->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link" data-tab-link="all" role="tab" aria-selected="false">
                    Todas @if ($tasks->count())
                        ({{ $tasks->count() }})
                    @endif
                </button>
            </div>

            <section class="tab-panel is-active" data-tab-panel="open">
                <div class="tab-panel-stack">
                    <x-page-header title="Tareas abiertas">
                        <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary">
                            Agregar tarea
                        </a>
                    </x-page-header>

                    <x-card class="list-card">
                        @include('tasks.partials.table', [
                            'tasks' => $openTasks,
                            'emptyMessage' => 'No hay tareas abiertas en este proyecto.',
                        ])
                    </x-card>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="done" hidden>
                <div class="tab-panel-stack">
                    <x-card class="list-card">
                        @include('tasks.partials.table', [
                            'tasks' => $doneTasks,
                            'emptyMessage' => 'No hay tareas finalizadas en este proyecto.',
                        ])
                    </x-card>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="all" hidden>
                <div class="tab-panel-stack">
                    <x-card class="list-card">
                        @include('tasks.partials.table', [
                            'tasks' => $tasks,
                            'emptyMessage' => 'No hay tareas asociadas a este proyecto.',
                        ])
                    </x-card>
                </div>
            </section>
        </div>

    </x-page>
@endsection
