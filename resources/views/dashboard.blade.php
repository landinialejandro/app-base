{{-- FILE: resources/views/dashboard.blade.php | V5 --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
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

        @if (
            $canAccessOrders ||
                $canAccessTasks ||
                $canAccessProjects ||
                $canAccessProducts ||
                $canAccessDocuments ||
                $canAccessInventory)
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

                    @if ($canAccessInventory)
                        <a href="{{ route('inventory.index') }}" class="dashboard-link-card">
                            <span class="dashboard-link-title">Inventario</span>
                            <span class="dashboard-link-text">Ver saldos por producto y abrir fichas operativas</span>
                            <span class="dashboard-link-meta">Stock y movimientos</span>
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
            @include('projects.partials.operational-analysis', [
                'projectOverview' => $projectOverview ?? [],
                'taskOverview' => $taskOverview ?? [],
            ])
        @endif
    </x-page>
@endsection
