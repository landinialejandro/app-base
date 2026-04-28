{{-- FILE: resources/views/dashboard.blade.php | V6 --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/modules/dashboard.css') }}">
@endpush

@php
    $dashboardIcon = static function (string $name): string {
        return match ($name) {
            'appointments' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v3M17 2v3M4 8h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/><path d="M8 12h3M8 16h5"/></svg>',
            'parties' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-8 0"/><path d="M4 20a8 8 0 0 1 16 0"/><path d="M17 8h3M18.5 6.5v3"/></svg>',
            'assets' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v10H4Z"/><path d="M7 17v3M17 17v3M8 11h8"/></svg>',
            'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10l3 3v15H4V6Z"/><path d="M17 3v4h4"/><path d="M8 11h8M8 15h6"/></svg>',
            'tasks' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h14M5 12h14M5 18h14"/><path d="M4 6l1 1 2-2M4 12l1 1 2-2M4 18l1 1 2-2"/></svg>',
            'projects' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h6l2 3h8v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><path d="M4 9h16"/></svg>',
            'products' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 7l8 4 8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/></svg>',
            'inventory' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v4H4Z"/><path d="M6 10h12v10H6Z"/><path d="M9 14h6"/></svg>',
            'documents' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7Z"/><path d="M14 3v6h5"/><path d="M10 13h6M10 17h4"/></svg>',
            default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v14H5Z"/></svg>',
        };
    };
@endphp

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

                <div class="dashboard-grid dashboard-grid--premium">
                    @if ($canAccessAppointments)
                        <a href="{{ route('appointments.calendar') }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--appointments">
                            <span class="dashboard-module-icon">{!! $dashboardIcon('appointments') !!}</span>
                            <span class="dashboard-module-watermark">{!! $dashboardIcon('appointments') !!}</span>
                            <span class="dashboard-link-title">Agenda</span>
                            <span class="dashboard-link-text">Ver calendario mensual y administrar turnos</span>
                            <span class="dashboard-link-meta">Calendario operativo</span>
                        </a>
                    @endif

                    @if ($canAccessParties)
                        <a href="{{ route('parties.index') }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--parties">
                            <span class="dashboard-module-icon">{!! $dashboardIcon('parties') !!}</span>
                            <span class="dashboard-module-watermark">{!! $dashboardIcon('parties') !!}</span>
                            <span class="dashboard-link-title">Contactos</span>
                            <span class="dashboard-link-text">Ver y administrar contactos</span>
                            <span class="dashboard-link-meta">{{ $partiesCount }} contactos</span>
                        </a>
                    @endif

                    @if ($canAccessAssets)
                        <a href="{{ route('assets.index') }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--assets">
                            <span class="dashboard-module-icon">{!! $dashboardIcon('assets') !!}</span>
                            <span class="dashboard-module-watermark">{!! $dashboardIcon('assets') !!}</span>
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

                <div class="dashboard-grid dashboard-grid--premium">
                    @if ($canAccessOrders)
                        <a href="{{ route('orders.index') }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--orders">
                            <span class="dashboard-module-icon">{!! $dashboardIcon('orders') !!}</span>
                            <span class="dashboard-module-watermark">{!! $dashboardIcon('orders') !!}</span>
                            <span class="dashboard-link-title">Órdenes</span>
                            <span class="dashboard-link-text">Ver y administrar órdenes</span>
                            <span class="dashboard-link-meta">{{ $ordersCount }} órdenes</span>
                        </a>
                    @endif

                    @if ($canAccessTasks)
                        <a href="{{ route('tasks.index') }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--tasks">
                            <span class="dashboard-module-icon">{!! $dashboardIcon('tasks') !!}</span>
                            <span class="dashboard-module-watermark">{!! $dashboardIcon('tasks') !!}</span>
                            <span class="dashboard-link-title">Tareas</span>
                            <span class="dashboard-link-text">Ver y administrar tareas</span>
                            <span class="dashboard-link-meta">Trabajo diario</span>
                        </a>
                    @endif

                    @if ($canAccessProjects)
                        <a href="{{ route('projects.index') }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--projects">
                            <span class="dashboard-module-icon">{!! $dashboardIcon('projects') !!}</span>
                            <span class="dashboard-module-watermark">{!! $dashboardIcon('projects') !!}</span>
                            <span class="dashboard-link-title">Proyectos</span>
                            <span class="dashboard-link-text">Ver y administrar proyectos</span>
                            <span class="dashboard-link-meta">Seguimiento operativo</span>
                        </a>
                    @endif

                    @if ($canAccessProducts)
                        <a href="{{ route('products.index') }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--products">
                            <span class="dashboard-module-icon">{!! $dashboardIcon('products') !!}</span>
                            <span class="dashboard-module-watermark">{!! $dashboardIcon('products') !!}</span>
                            <span class="dashboard-link-title">Productos</span>
                            <span class="dashboard-link-text">Ver y administrar productos y servicios</span>
                            <span class="dashboard-link-meta">{{ $productsCount }} productos</span>
                        </a>
                    @endif

                    @if ($canAccessInventory)
                        <a href="{{ route('inventory.index') }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--inventory">
                            <span class="dashboard-module-icon">{!! $dashboardIcon('inventory') !!}</span>
                            <span class="dashboard-module-watermark">{!! $dashboardIcon('inventory') !!}</span>
                            <span class="dashboard-link-title">Inventario</span>
                            <span class="dashboard-link-text">Ver saldos por producto y abrir fichas operativas</span>
                            <span class="dashboard-link-meta">Stock y movimientos</span>
                        </a>
                    @endif

                    @if ($canAccessDocuments)
                        <a href="{{ route('documents.index') }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--documents">
                            <span class="dashboard-module-icon">{!! $dashboardIcon('documents') !!}</span>
                            <span class="dashboard-module-watermark">{!! $dashboardIcon('documents') !!}</span>
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