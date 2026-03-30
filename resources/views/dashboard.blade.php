{{-- FILE: resources/views/dashboard.blade.php | V3 --}}

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
            $visible_tasks_count = $visible_tasks_count ?? 0;
            $my_overdue_tasks_count = $my_overdue_tasks_count ?? 0;
        }
    @endphp

    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio']]" />

        <x-page-header title="Dashboard" />

        @if ($canAccessAppointments || $canAccessParties || $canAccessAssets)
            <x-card>
                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Operación diaria</h2>
                    <p class="dashboard-section-text">Accesos principales para el trabajo cotidiano.</p>
                </div>

                <div class="dashboard-grid">
                    @if ($canAccessAppointments)
                        <a href="{{ route('appointments.calendar') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Agenda</span>
                            <span class="dashboard-link-text">Ver calendario mensual y administrar turnos</span>
                            <span class="dashboard-link-meta">Calendario operativo</span>
                        </a>
                    @endif

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
                </div>
            </x-card>
        @endif

        @if ($canAccessOrders || $canAccessTasks || $canAccessProjects || $canAccessProducts || $canAccessDocuments)
            <x-card>
                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Gestión complementaria</h2>
                    <p class="dashboard-section-text">Módulos de seguimiento interno, planificación y soporte.</p>
                </div>

                <div class="dashboard-grid">
                    @if ($canAccessOrders)
                        <a href="{{ route('orders.index') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Órdenes</span>
                            <span class="dashboard-link-text">Ver y administrar órdenes</span>
                            <span class="dashboard-link-meta">{{ $ordersCount }} órdenes</span>
                        </a>
                    @endif

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
                    toggle-label-expanded="Ocultar análisis" details-layout="raw">

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
                        @include('projects.partials.operational-analysis', [
                            'visible_projects_count' => $visible_projects_count,
                            'active_projects_count' => $active_projects_count ?? 0,
                            'closed_projects_count' => $closed_projects_count ?? 0,
                            'projects_with_open_tasks_count' => $projects_with_open_tasks_count ?? 0,
                            'projects_with_overdue_tasks_count' => $projects_with_overdue_tasks_count ?? 0,
                            'projects_average_progress' => $projects_average_progress ?? 0,
                            'visible_tasks_count' => $visible_tasks_count,
                            'pending_tasks_count' => $pending_tasks_count ?? 0,
                            'in_progress_tasks_count' => $in_progress_tasks_count ?? 0,
                            'done_tasks_count' => $done_tasks_count ?? 0,
                            'cancelled_tasks_count' => $cancelled_tasks_count ?? 0,
                        ])
                    </x-slot:details>
                </x-show-summary>
            </x-card>
        @endif
    </x-page>
@endsection
