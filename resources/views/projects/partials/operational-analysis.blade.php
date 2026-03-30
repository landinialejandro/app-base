{{-- FILE: resources/views/projects/partials/operational-analysis.blade.php | V7 --}}

@php
    $projectOverview = $projectOverview ?? [];
    $taskOverview = $taskOverview ?? [];

    extract($projectOverview, EXTR_SKIP);
    extract($taskOverview, EXTR_SKIP);

    $visible_projects_count = $visible_projects_count ?? 0;
    $active_projects_count = $active_projects_count ?? 0;
    $closed_projects_count = $closed_projects_count ?? 0;
    $projects_with_open_tasks_count = $projects_with_open_tasks_count ?? 0;
    $projects_with_overdue_tasks_count = $projects_with_overdue_tasks_count ?? 0;
    $projects_average_progress = $projects_average_progress ?? 0;

    $visible_tasks_count = $visible_tasks_count ?? 0;
    $pending_tasks_count = $pending_tasks_count ?? 0;
    $in_progress_tasks_count = $in_progress_tasks_count ?? 0;
    $done_tasks_count = $done_tasks_count ?? 0;
    $cancelled_tasks_count = $cancelled_tasks_count ?? 0;
    $my_overdue_tasks_count = $my_overdue_tasks_count ?? 0;
@endphp

<x-analytics.panel title="Análisis operativo" subtitle="Resumen visual de proyectos y tareas visibles para esta empresa."
    details-id="project-analytics-detail" toggle-label="Ver análisis" toggle-label-expanded="Ocultar análisis">

    <x-slot:summary>
        <x-analytics.kpi-card title="Proyectos visibles" :value="$visible_projects_count" />

        <x-analytics.kpi-card title="Tareas visibles" :value="$visible_tasks_count" />

        <x-analytics.kpi-card title="Mis tareas vencidas" :value="$my_overdue_tasks_count" />
    </x-slot:summary>

    <x-slot:details>
        {{-- Fila 1: dos gráficos en 50/50 --}}
        <div class="analytics-grid analytics-grid--2">
            <x-analytics.card title="Estado de proyectos">
                <x-analytics.pie-chart :total="$visible_projects_count" center-label="proyectos" :segments="[
                    [
                        'label' => 'Activos',
                        'count' => $active_projects_count,
                        'segment_class' => 'pie-segment--done',
                        'dot_class' => 'pie-dot--done',
                    ],
                    [
                        'label' => 'Cerrados',
                        'count' => $closed_projects_count,
                        'segment_class' => 'pie-segment--cancelled',
                        'dot_class' => 'pie-dot--cancelled',
                    ],
                ]" />
            </x-analytics.card>

            <x-analytics.card title="Estado de tareas">
                <x-analytics.pie-chart :total="$visible_tasks_count" center-label="tareas" :segments="[
                    [
                        'label' => 'Pendientes',
                        'count' => $pending_tasks_count,
                        'segment_class' => 'pie-segment--pending',
                        'dot_class' => 'pie-dot--pending',
                    ],
                    [
                        'label' => 'En progreso',
                        'count' => $in_progress_tasks_count,
                        'segment_class' => 'pie-segment--in-progress',
                        'dot_class' => 'pie-dot--in-progress',
                    ],
                    [
                        'label' => 'Finalizadas',
                        'count' => $done_tasks_count,
                        'segment_class' => 'pie-segment--done',
                        'dot_class' => 'pie-dot--done',
                    ],
                    [
                        'label' => 'Canceladas',
                        'count' => $cancelled_tasks_count,
                        'segment_class' => 'pie-segment--cancelled',
                        'dot_class' => 'pie-dot--cancelled',
                    ],
                ]" />
            </x-analytics.card>
        </div>

        {{-- Fila 2: 3 KPI --}}
        <div class="analytics-grid">
            <x-analytics.kpi-card title="Proyectos con tareas abiertas" :value="$projects_with_open_tasks_count"
                note="Proyectos visibles aún en ejecución" />

            <x-analytics.kpi-card title="Proyectos con vencidas" :value="$projects_with_overdue_tasks_count"
                note="Proyectos visibles con tareas demoradas" />

            <x-analytics.kpi-card title="Avance promedio" :value="$projects_average_progress . '%'"
                note="Promedio simple entre proyectos visibles" />
        </div>
    </x-slot:details>
</x-analytics.panel>
