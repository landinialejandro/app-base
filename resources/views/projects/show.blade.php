{{-- FILE: resources/views/projects/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle del proyecto')

@section('content')
    @php
        use App\Support\Catalogs\ProjectCatalog;

        extract($metrics, EXTR_SKIP);

        $canDeleteProject = auth()->user()->can('delete', $project);
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

        <x-show-summary details-id="project-more-detail">
            <x-show-summary-item label="Nombre">
                {{ $project->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Estado">
                <span class="status-badge {{ ProjectCatalog::badgeClass($project->status) }}">
                    {{ ProjectCatalog::label($project->status) }}
                </span>
            </x-show-summary-item>

            <x-show-summary-item label="Tareas">
                {{ $tasks->count() }}
            </x-show-summary-item>

            <x-slot:details>
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
            </x-slot:details>
        </x-show-summary>

        <x-show-summary details-id="project-metrics-detail" toggle-label="Ver análisis"
            toggle-label-expanded="Ocultar análisis">
            <x-show-summary-item label="Abiertas">
                {{ $openTasks->count() }}
            </x-show-summary-item>

            <x-show-summary-item label="Avance">
                {{ $progress }}%
            </x-show-summary-item>

            <x-show-summary-item label="Vencidas">
                {{ $overdueCount }}
            </x-show-summary-item>

            <x-slot:details>
                <div class="project-visual-detail">
                    <div class="project-visual-grid">
                        <div class="project-visual-card">
                            <div class="project-visual-title">Progreso general</div>

                            <div class="project-progress">
                                <div class="project-progress-bar" style="width: {{ $progress }}%;"></div>
                            </div>

                            <div class="project-progress-meta">
                                <span>{{ $doneCount }} de {{ $totalTasks }} tareas finalizadas</span>
                                <strong>{{ $progress }}%</strong>
                            </div>
                        </div>

                        <div class="project-visual-card">
                            <div class="project-visual-title">Estados de tareas</div>

                            @if ($totalTasks > 0 && count($pieSegments) > 0)
                                <div class="project-pie-layout">
                                    <div class="project-pie-chart-wrap">
                                        <svg viewBox="0 0 42 42" class="project-pie-chart" aria-hidden="true">
                                            <circle class="project-pie-track" cx="21" cy="21" r="15.9155">
                                            </circle>

                                            @foreach ($pieSegments as $segment)
                                                <circle class="project-pie-segment {{ $segment['class'] }}" cx="21"
                                                    cy="21" r="15.9155" stroke-dasharray="{{ $segment['dash'] }}"
                                                    stroke-dashoffset="{{ $segment['offset'] }}"></circle>
                                            @endforeach
                                        </svg>

                                        <div class="project-pie-center">
                                            <strong>{{ $totalTasks }}</strong>
                                            <span>tareas</span>
                                        </div>
                                    </div>

                                    <div class="project-pie-legend">
                                        <div class="project-pie-legend-item">
                                            <span class="project-pie-dot project-pie-dot--pending"></span>
                                            <span>Pendientes: {{ $pendingCount }}</span>
                                        </div>

                                        <div class="project-pie-legend-item">
                                            <span class="project-pie-dot project-pie-dot--in-progress"></span>
                                            <span>En progreso: {{ $inProgressCount }}</span>
                                        </div>

                                        <div class="project-pie-legend-item">
                                            <span class="project-pie-dot project-pie-dot--done"></span>
                                            <span>Finalizadas: {{ $doneCount }}</span>
                                        </div>

                                        <div class="project-pie-legend-item">
                                            <span class="project-pie-dot project-pie-dot--cancelled"></span>
                                            <span>Canceladas: {{ $cancelledCount }}</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="mb-0">No hay tareas suficientes para graficar.</p>
                            @endif
                        </div>
                    </div>

                    <div class="project-visual-grid project-visual-grid--stats">
                        <div class="project-visual-card">
                            <div class="project-visual-title">Tiempo transcurrido</div>
                            <div class="project-visual-kpi">
                                {{ is_null($daysElapsed) ? '—' : $daysElapsed . ' días' }}
                            </div>
                            <div class="project-visual-note">
                                Desde {{ $projectStartDate?->format('d/m/Y') ?: '—' }}
                            </div>
                        </div>

                        <div class="project-visual-card">
                            <div class="project-visual-title">Días hasta el fin previsto</div>
                            <div class="project-visual-kpi">
                                @if (is_null($daysRemaining))
                                    —
                                @elseif($daysRemaining < 0)
                                    Vencido
                                @else
                                    {{ $daysRemaining }} días
                                @endif
                            </div>
                            <div class="project-visual-note">
                                @if ($lastOpenDueDate)
                                    Fecha estimada: {{ $lastOpenDueDate->format('d/m/Y') }}
                                @else
                                    Sin tareas abiertas con vencimiento
                                @endif
                            </div>
                        </div>

                        <div class="project-visual-card">
                            <div class="project-visual-title">Tareas vencidas</div>
                            <div class="project-visual-kpi">{{ $overdueCount }}</div>
                            <div class="project-visual-note">Pendientes fuera de fecha</div>
                        </div>
                    </div>
                </div>
            </x-slot:details>
        </x-show-summary>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Tareas del proyecto">
                <button type="button" class="tabs-link is-active" data-tab-link="open" role="tab"
                    aria-selected="true">
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
