{{-- FILE: resources/views/projects/show.blade.php | V9 --}}

@extends('layouts.app')

@section('title', 'Detalle del proyecto')

@section('content')
    @php
        use App\Support\Catalogs\ProjectCatalog;
        use App\Support\Navigation\NavigationTrail;

        extract($metrics, EXTR_SKIP);

        $attachments = $project->attachments ?? collect();
        $canDeleteProject = auth()->user()->can('delete', $project);
        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('projects.index'));
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del proyecto">
            <a href="{{ route('projects.edit', ['project' => $project] + $trailQuery) }}" class="btn btn-primary">
                <x-icons.pencil />
                <span>Editar</span>
            </a>

            @if ($canDeleteProject)
                <form method="POST" action="{{ route('projects.destroy', ['project' => $project] + $trailQuery) }}"
                    class="inline-form" data-action="app-confirm-submit"
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

            <a href="{{ $backUrl }}" class="btn btn-secondary">
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
                <x-show-summary-item-detail-block label="Pendientes">
                    {{ $pendingCount }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="En progreso">
                    {{ $inProgressCount }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Vencidas">
                    {{ $overdueCount }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Finalizadas">
                    {{ $doneCount }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Canceladas">
                    {{ $cancelledCount }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado">
                    {{ $project->updated_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Descripción" full>
                    {{ $project->description ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <x-show-summary details-id="project-metrics-detail" toggle-label="Ver análisis"
            toggle-label-expanded="Ocultar análisis">
            <x-show-summary-item label="Abiertas">
                {{ $openTasks->count() }}
            </x-show-summary-item>

            <x-show-summary-item label="Avance">
                <div class="summary-progress-inline">
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $progress }}%;"></div>
                    </div>

                    <div class="summary-progress-value">{{ $progress }}%</div>
                </div>
            </x-show-summary-item>

            <x-show-summary-item label="Vencidas">
                {{ $overdueCount }}
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item label="Estados de tareas" span="3">
                    @if ($totalTasks > 0 && count($pieSegments) > 0)
                        <div class="pie-layout pie-layout--wide">
                            <div class="pie-chart-wrap">
                                <svg viewBox="0 0 42 42" class="pie-chart" aria-hidden="true">
                                    <circle class="pie-track" cx="21" cy="21" r="15.9155"></circle>

                                    @foreach ($pieSegments as $segment)
                                        <circle class="pie-segment {{ $segment['class'] }}" cx="21" cy="21"
                                            r="15.9155" stroke-dasharray="{{ $segment['dash'] }}"
                                            stroke-dashoffset="{{ $segment['offset'] }}"></circle>
                                    @endforeach
                                </svg>

                                <div class="pie-center">
                                    <strong>{{ $totalTasks }}</strong>
                                    <span>tareas</span>
                                </div>
                            </div>

                            <div class="pie-legend">
                                <div class="pie-legend-item">
                                    <span class="pie-dot pie-dot--pending"></span>
                                    <span>Pendientes: {{ $pendingCount }}</span>
                                </div>

                                <div class="pie-legend-item">
                                    <span class="pie-dot pie-dot--in-progress"></span>
                                    <span>En progreso: {{ $inProgressCount }}</span>
                                </div>

                                <div class="pie-legend-item">
                                    <span class="pie-dot pie-dot--done"></span>
                                    <span>Finalizadas: {{ $doneCount }}</span>
                                </div>

                                <div class="pie-legend-item">
                                    <span class="pie-dot pie-dot--cancelled"></span>
                                    <span>Canceladas: {{ $cancelledCount }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="mb-0">No hay tareas suficientes para graficar.</p>
                    @endif
                </x-show-summary-item>

                <x-show-summary-item label="Tiempo transcurrido">
                    <div class="visual-kpi">
                        {{ is_null($daysElapsed) ? '—' : $daysElapsed . ' días' }}
                    </div>
                    <div class="visual-note">
                        Desde {{ $projectStartDate?->format('d/m/Y') ?: '—' }}
                    </div>
                </x-show-summary-item>

                <x-show-summary-item label="Días hasta el fin previsto">
                    <div class="visual-kpi">
                        @if (is_null($daysRemaining))
                            —
                        @elseif($daysRemaining < 0)
                            Vencido
                        @else
                            {{ $daysRemaining }} días
                        @endif
                    </div>
                    <div class="visual-note">
                        @if ($lastOpenDueDate)
                            Fecha estimada: {{ $lastOpenDueDate->format('d/m/Y') }}
                        @else
                            Sin tareas abiertas con vencimiento
                        @endif
                    </div>
                </x-show-summary-item>

                <x-show-summary-item label="Tareas vencidas">
                    <div class="visual-kpi">{{ $overdueCount }}</div>
                    <div class="visual-note">Pendientes fuera de fecha</div>
                </x-show-summary-item>
            </x-slot:details>
        </x-show-summary>

        <div class="tabs" data-tabs>
            <x-tab-toolbar label="Secciones del proyecto">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones del proyecto">
                        <button type="button" class="tabs-link is-active" data-tab-link="tasks" role="tab"
                            aria-selected="true">
                            Tareas
                            @if ($tasks->count())
                                ({{ $tasks->count() }})
                            @endif
                        </button>

                        <button type="button" class="tabs-link" data-tab-link="attachments" role="tab"
                            aria-selected="false">
                            Adjuntos
                            @if ($attachments->count())
                                ({{ $attachments->count() }})
                            @endif
                        </button>
                    </x-horizontal-scroll>
                </x-slot:tabs>
            </x-tab-toolbar>

            <section class="tab-panel is-active" data-tab-panel="tasks">
                <div class="tab-panel-stack">
                    @include('tasks.partials.embedded-tabs', [
                        'tasks' => $tasks,
                        'openTasks' => $openTasks,
                        'doneTasks' => $doneTasks,
                        'emptyMessageOpen' => 'No hay tareas abiertas en este proyecto.',
                        'emptyMessageDone' => 'No hay tareas finalizadas en este proyecto.',
                        'emptyMessageAll' => 'No hay tareas asociadas a este proyecto.',
                        'tabsId' => 'project-tasks-tabs',
                        'createBaseQuery' => [
                            'project_id' => $project->id,
                        ],
                        'trailQuery' => $trailQuery,
                    ])
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="attachments" hidden>
                <div class="tab-panel-stack">
                    @include('attachments.partials.embedded', [
                        'attachments' => $attachments,
                        'attachableType' => 'project',
                        'attachableId' => $project->id,
                        'trailQuery' => $trailQuery,
                        'returnTo' => url()->current(),
                        'tabsId' => 'project-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>
        </div>

    </x-page>
@endsection
