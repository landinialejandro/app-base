{{-- FILE: resources/views/projects/show.blade.php | V17 --}}

@extends('layouts.app')

@section('title', 'Detalle del proyecto')

@section('content')
    @php
        use App\Support\Catalogs\ProjectCatalog;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Ui\HostTabs;

        $metrics = $metrics ?? [];

        extract($metrics, EXTR_SKIP);

        $tasks = $tasks ?? ($project->tasks ?? collect());
        $openTasks = $openTasks ?? collect();
        $doneTasks = $doneTasks ?? collect();

        $pendingCount = $pendingCount ?? 0;
        $inProgressCount = $inProgressCount ?? 0;
        $overdueCount = $overdueCount ?? 0;
        $doneCount = $doneCount ?? 0;
        $cancelledCount = $cancelledCount ?? 0;

        $attachments = $project->attachments ?? collect();

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('projects.index'));
        $tabsLabel = 'Secciones del proyecto';

        $tabItems = collect([
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'tasks',
                'label' => 'Tareas',
                'priority' => 10,
                'count' => $tasks->count(),
                'view' => 'tasks.partials.embedded-tabs',
                'data' => [
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
                ],
            ],
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'attachments',
                'label' => 'Adjuntos',
                'priority' => 20,
                'count' => $attachments->count(),
                'view' => 'attachments.partials.embedded',
                'data' => [
                    'attachments' => $attachments,
                    'attachable' => $project,
                    'attachableType' => 'project',
                    'attachableId' => $project->id,
                    'trailQuery' => $trailQuery,
                    'navigationTrail' => $navigationTrail,
                    'tabsId' => 'project-attachments-tabs',
                    'createLabel' => 'Agregar adjunto',
                ],
            ],
        ])
            ->sortBy(fn($item) => $item['priority'] ?? 999)
            ->values();

        $activeTab = HostTabs::activeKey($tabItems, request()->query('return_tab'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del proyecto">
            @can('update', $project)
                <x-button-edit :href="route('projects.edit', ['project' => $project] + $trailQuery)" />
            @endcan

            @can('delete', $project)
                <x-button-delete :action="route('projects.destroy', ['project' => $project] + $trailQuery)" :message="$tasks->count()
                    ? 'Este proyecto tiene tareas asociadas. Si lo eliminas, también se eliminarán sus tareas. ¿Deseas continuar?'
                    : '¿Deseas eliminar este proyecto?'" />
            @endcan

            <x-button-back :href="$backUrl" />
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

        @include('projects.partials.show-analytics', [
            'metrics' => $metrics,
        ])

        <x-host-tabs :items="$tabItems" :active-tab="$activeTab" :label="$tabsLabel" />
    </x-page>
@endsection
