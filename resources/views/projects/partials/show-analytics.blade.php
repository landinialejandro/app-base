{{-- FILE: resources/views/projects/partials/show-analytics.blade.php | V5 --}}

@php
    $metrics = $metrics ?? [];
    extract($metrics, EXTR_SKIP);

    $openTasks = $openTasks ?? collect();
    $progress = $progress ?? 0;
    $overdueCount = $overdueCount ?? 0;

    $totalTasks = $totalTasks ?? 0;
    $pendingCount = $pendingCount ?? 0;
    $inProgressCount = $inProgressCount ?? 0;
    $doneCount = $doneCount ?? 0;
    $cancelledCount = $cancelledCount ?? 0;

    $daysElapsed = $daysElapsed ?? null;
    $projectStartDate = $projectStartDate ?? null;
    $daysRemaining = $daysRemaining ?? null;
    $lastOpenDueDate = $lastOpenDueDate ?? null;
@endphp

<x-analytics.panel title="Análisis del proyecto" subtitle="Resumen operativo del estado actual del proyecto."
    details-id="project-analytics-detail" toggle-label="Ver análisis" toggle-label-expanded="Ocultar análisis">

    <x-slot:summary>
        <x-analytics.kpi-card title="Tiempo transcurrido" :value="is_null($daysElapsed) ? '—' : $daysElapsed . ' días'" :note="'Desde ' . ($projectStartDate?->format('d/m/Y') ?: '—')" />

        <x-analytics.kpi-card title="Días hasta el fin previsto" :value="is_null($daysRemaining) ? '—' : ($daysRemaining < 0 ? 'Vencido' : $daysRemaining . ' días')"
            :note="$lastOpenDueDate ? 'Fecha estimada: ' . $lastOpenDueDate->format('d/m/Y') : 'Sin tareas abiertas con vencimiento'" />

        <x-analytics.kpi-card title="Tareas vencidas" :value="$overdueCount" note="Pendientes fuera de fecha" />
    </x-slot:summary>

    <x-slot:details>
        <div class="analytics-grid analytics-grid--2">
            <x-analytics.card title="Estados de tareas">
                <x-analytics.pie-chart :total="$totalTasks" center-label="tareas" :segments="[
                    [
                        'label' => 'Pendientes',
                        'count' => $pendingCount,
                        'segment_class' => 'pie-segment--pending',
                        'dot_class' => 'pie-dot--pending',
                    ],
                    [
                        'label' => 'En progreso',
                        'count' => $inProgressCount,
                        'segment_class' => 'pie-segment--in-progress',
                        'dot_class' => 'pie-dot--in-progress',
                    ],
                    [
                        'label' => 'Finalizadas',
                        'count' => $doneCount,
                        'segment_class' => 'pie-segment--done',
                        'dot_class' => 'pie-dot--done',
                    ],
                    [
                        'label' => 'Canceladas',
                        'count' => $cancelledCount,
                        'segment_class' => 'pie-segment--cancelled',
                        'dot_class' => 'pie-dot--cancelled',
                    ],
                ]"
                    empty-message="No hay tareas suficientes para graficar." />
            </x-analytics.card>

            <x-analytics.card title="Avance del proyecto">
                <div class="summary-progress-inline">
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $progress }}%;"></div>
                    </div>

                    <div class="summary-progress-value">{{ $progress }}%</div>
                </div>

                <div class="visual-note">
                    {{ $openTasks->count() }} tareas abiertas de {{ $totalTasks }} totales
                </div>
            </x-analytics.card>
        </div>
    </x-slot:details>
</x-analytics.panel>
