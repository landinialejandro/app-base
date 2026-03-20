{{-- FILE: resources/views/dashboard.blade.php --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $projectOverview = $projectOverview ?? [];
        $taskOverview = $taskOverview ?? [];

        if ($canSeeAnalytics) {
            extract($projectOverview, EXTR_SKIP);
            extract($taskOverview, EXTR_SKIP);

            $visible_projects_count = $visible_projects_count ?? 0;
            $active_projects_count = $active_projects_count ?? 0;
            $closed_projects_count = $closed_projects_count ?? 0;
            $projects_with_open_tasks_count = $projects_with_open_tasks_count ?? 0;
            $projects_with_overdue_tasks_count = $projects_with_overdue_tasks_count ?? 0;
            $projects_average_progress = $projects_average_progress ?? 0;

            $visible_tasks_count = $visible_tasks_count ?? 0;
            $my_tasks_count = $my_tasks_count ?? 0;
            $pending_tasks_count = $pending_tasks_count ?? 0;
            $in_progress_tasks_count = $in_progress_tasks_count ?? 0;
            $done_tasks_count = $done_tasks_count ?? 0;
            $cancelled_tasks_count = $cancelled_tasks_count ?? 0;
            $overdue_tasks_count = $overdue_tasks_count ?? 0;
            $my_overdue_tasks_count = $my_overdue_tasks_count ?? 0;

            $totalProjectStates = $active_projects_count + $closed_projects_count;
            $activeProjectsPercent =
                $totalProjectStates > 0 ? round(($active_projects_count / $totalProjectStates) * 100, 2) : 0;
            $closedProjectsPercent =
                $totalProjectStates > 0 ? round(($closed_projects_count / $totalProjectStates) * 100, 2) : 0;

            $projectSegments = [];
            $projectOffset = 0;

            foreach (
                [
                    [
                        'count' => $active_projects_count,
                        'percent' => $activeProjectsPercent,
                        'class' => 'dashboard-pie-segment--done',
                        'label' => 'Activos',
                    ],
                    [
                        'count' => $closed_projects_count,
                        'percent' => $closedProjectsPercent,
                        'class' => 'dashboard-pie-segment--cancelled',
                        'label' => 'Cerrados',
                    ],
                ]
                as $segment
            ) {
                if ($segment['count'] <= 0) {
                    continue;
                }

                $projectSegments[] = [
                    ...$segment,
                    'dash' => $segment['percent'] . ' ' . (100 - $segment['percent']),
                    'offset' => -$projectOffset,
                ];

                $projectOffset += $segment['percent'];
            }

            $totalTaskStates =
                $pending_tasks_count + $in_progress_tasks_count + $done_tasks_count + $cancelled_tasks_count;

            $pendingTasksPercent = $totalTaskStates > 0 ? round(($pending_tasks_count / $totalTaskStates) * 100, 2) : 0;
            $inProgressTasksPercent =
                $totalTaskStates > 0 ? round(($in_progress_tasks_count / $totalTaskStates) * 100, 2) : 0;
            $doneTasksPercent = $totalTaskStates > 0 ? round(($done_tasks_count / $totalTaskStates) * 100, 2) : 0;
            $cancelledTasksPercent =
                $totalTaskStates > 0 ? round(($cancelled_tasks_count / $totalTaskStates) * 100, 2) : 0;

            $taskSegments = [];
            $taskOffset = 0;

            foreach (
                [
                    [
                        'count' => $pending_tasks_count,
                        'percent' => $pendingTasksPercent,
                        'class' => 'dashboard-pie-segment--pending',
                        'label' => 'Pendientes',
                    ],
                    [
                        'count' => $in_progress_tasks_count,
                        'percent' => $inProgressTasksPercent,
                        'class' => 'dashboard-pie-segment--in-progress',
                        'label' => 'En progreso',
                    ],
                    [
                        'count' => $done_tasks_count,
                        'percent' => $doneTasksPercent,
                        'class' => 'dashboard-pie-segment--done',
                        'label' => 'Finalizadas',
                    ],
                    [
                        'count' => $cancelled_tasks_count,
                        'percent' => $cancelledTasksPercent,
                        'class' => 'dashboard-pie-segment--cancelled',
                        'label' => 'Canceladas',
                    ],
                ]
                as $segment
            ) {
                if ($segment['count'] <= 0) {
                    continue;
                }

                $taskSegments[] = [
                    ...$segment,
                    'dash' => $segment['percent'] . ' ' . (100 - $segment['percent']),
                    'offset' => -$taskOffset,
                ];

                $taskOffset += $segment['percent'];
            }
        }
    @endphp

    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio']]" />

        <x-page-header title="Dashboard" />

        @if ($canAccessParties || $canAccessAssets || $canAccessOrders)
            <x-card>
                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Operación diaria</h2>
                    <p class="dashboard-section-text">Accesos principales para el trabajo cotidiano.</p>
                </div>

                <div class="dashboard-grid">
                    @if ($canAccessParties)
                        <a href="{{ route('parties.index') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Contactos</span>
                            <span class="dashboard-link-text">Ver y administrar contactos</span>
                            <span class="dashboard-link-meta">{{ $partiesCount }} contactos</span>
                        </a>
                    @endif

                    @if ($canAccessAssets)
                        <a href="{{ route('assets.index') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Activos</span>
                            <span class="dashboard-link-text">Ver y administrar activos operativos</span>
                            <span class="dashboard-link-meta">{{ $assetsCount }} activos</span>
                        </a>
                    @endif

                    @if ($canAccessOrders)
                        <a href="{{ route('orders.index') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Órdenes</span>
                            <span class="dashboard-link-text">Ver y administrar órdenes</span>
                            <span class="dashboard-link-meta">{{ $ordersCount }} órdenes</span>
                        </a>
                    @endif
                </div>
            </x-card>
        @endif

        @if ($canAccessTasks || $canAccessProjects || $canAccessProducts || $canAccessDocuments)
            <x-card>
                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Gestión complementaria</h2>
                    <p class="dashboard-section-text">Módulos de seguimiento interno, planificación y soporte.</p>
                </div>

                <div class="dashboard-grid">
                    @if ($canAccessTasks)
                        <a href="{{ route('tasks.index') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Tareas</span>
                            <span class="dashboard-link-text">Ver y administrar tareas</span>
                            <span class="dashboard-link-meta">Trabajo diario</span>
                        </a>
                    @endif

                    @if ($canAccessProjects)
                        <a href="{{ route('projects.index') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Proyectos</span>
                            <span class="dashboard-link-text">Ver y administrar proyectos</span>
                            <span class="dashboard-link-meta">Seguimiento operativo</span>
                        </a>
                    @endif

                    @if ($canAccessProducts)
                        <a href="{{ route('products.index') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Productos</span>
                            <span class="dashboard-link-text">Ver y administrar productos y servicios</span>
                            <span class="dashboard-link-meta">{{ $productsCount }} productos</span>
                        </a>
                    @endif

                    @if ($canAccessDocuments)
                        <a href="{{ route('documents.index') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Documentos</span>
                            <span class="dashboard-link-text">Ver y administrar documentos comerciales</span>
                            <span class="dashboard-link-meta">{{ $documentsCount }} documentos</span>
                        </a>
                    @endif
                </div>
            </x-card>
        @endif

        @if ($canSeeAnalytics)
            <x-card>
                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Análisis operativo</h2>
                    <p class="dashboard-section-text">Resumen visual de proyectos y tareas visibles para esta empresa.</p>
                </div>

                <x-show-summary details-id="dashboard-analytics-detail" toggle-label="Ver análisis"
                    toggle-label-expanded="Ocultar análisis">

                    <x-show-summary-item label="Proyectos visibles">
                        {{ $visible_projects_count }}
                    </x-show-summary-item>

                    <x-show-summary-item label="Tareas visibles">
                        {{ $visible_tasks_count }}
                    </x-show-summary-item>

                    <x-show-summary-item label="Mis tareas vencidas">
                        {{ $my_overdue_tasks_count }}
                    </x-show-summary-item>

                    <x-slot:details>
                        <div class="project-visual-detail">
                            <div class="project-visual-grid">
                                <div class="project-visual-card">
                                    <div class="project-visual-title">Estado de proyectos</div>

                                    @if ($totalProjectStates > 0 && count($projectSegments) > 0)
                                        <div class="project-pie-layout">
                                            <div class="project-pie-chart-wrap">
                                                <svg viewBox="0 0 42 42" class="project-pie-chart" aria-hidden="true">
                                                    <circle class="project-pie-track" cx="21" cy="21"
                                                        r="15.9155"></circle>

                                                    @foreach ($projectSegments as $segment)
                                                        <circle class="project-pie-segment {{ $segment['class'] }}"
                                                            cx="21" cy="21" r="15.9155"
                                                            stroke-dasharray="{{ $segment['dash'] }}"
                                                            stroke-dashoffset="{{ $segment['offset'] }}"></circle>
                                                    @endforeach
                                                </svg>

                                                <div class="project-pie-center">
                                                    <strong>{{ $visible_projects_count }}</strong>
                                                    <span>proyectos</span>
                                                </div>
                                            </div>

                                            <div class="project-pie-legend">
                                                <div class="project-pie-legend-item">
                                                    <span class="project-pie-dot project-pie-dot--done"></span>
                                                    <span>Activos: {{ $active_projects_count }}</span>
                                                </div>

                                                <div class="project-pie-legend-item">
                                                    <span class="project-pie-dot project-pie-dot--cancelled"></span>
                                                    <span>Cerrados: {{ $closed_projects_count }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <p class="mb-0">No hay proyectos suficientes para graficar.</p>
                                    @endif
                                </div>

                                <div class="project-visual-card">
                                    <div class="project-visual-title">Estado de tareas</div>

                                    @if ($totalTaskStates > 0 && count($taskSegments) > 0)
                                        <div class="project-pie-layout">
                                            <div class="project-pie-chart-wrap">
                                                <svg viewBox="0 0 42 42" class="project-pie-chart" aria-hidden="true">
                                                    <circle class="project-pie-track" cx="21" cy="21"
                                                        r="15.9155"></circle>

                                                    @foreach ($taskSegments as $segment)
                                                        <circle class="project-pie-segment {{ $segment['class'] }}"
                                                            cx="21" cy="21" r="15.9155"
                                                            stroke-dasharray="{{ $segment['dash'] }}"
                                                            stroke-dashoffset="{{ $segment['offset'] }}"></circle>
                                                    @endforeach
                                                </svg>

                                                <div class="project-pie-center">
                                                    <strong>{{ $visible_tasks_count }}</strong>
                                                    <span>tareas</span>
                                                </div>
                                            </div>

                                            <div class="project-pie-legend">
                                                <div class="project-pie-legend-item">
                                                    <span class="project-pie-dot project-pie-dot--pending"></span>
                                                    <span>Pendientes: {{ $pending_tasks_count }}</span>
                                                </div>

                                                <div class="project-pie-legend-item">
                                                    <span class="project-pie-dot project-pie-dot--in-progress"></span>
                                                    <span>En progreso: {{ $in_progress_tasks_count }}</span>
                                                </div>

                                                <div class="project-pie-legend-item">
                                                    <span class="project-pie-dot project-pie-dot--done"></span>
                                                    <span>Finalizadas: {{ $done_tasks_count }}</span>
                                                </div>

                                                <div class="project-pie-legend-item">
                                                    <span class="project-pie-dot project-pie-dot--cancelled"></span>
                                                    <span>Canceladas: {{ $cancelled_tasks_count }}</span>
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
                                    <div class="project-visual-title">Proyectos con tareas abiertas</div>
                                    <div class="project-visual-kpi">{{ $projects_with_open_tasks_count }}</div>
                                    <div class="project-visual-note">Proyectos visibles aún en ejecución</div>
                                </div>

                                <div class="project-visual-card">
                                    <div class="project-visual-title">Proyectos con vencidas</div>
                                    <div class="project-visual-kpi">{{ $projects_with_overdue_tasks_count }}</div>
                                    <div class="project-visual-note">Proyectos visibles con tareas demoradas</div>
                                </div>

                                <div class="project-visual-card">
                                    <div class="project-visual-title">Avance promedio</div>
                                    <div class="project-visual-kpi">{{ $projects_average_progress }}%</div>
                                    <div class="project-visual-note">Promedio simple entre proyectos visibles</div>
                                </div>
                            </div>
                        </div>
                    </x-slot:details>
                </x-show-summary>
            </x-card>
        @endif
    </x-page>
@endsection
