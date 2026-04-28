{{-- FILE: resources/views/dashboard.blade.php | V9 --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/modules/dashboard.css') }}">
@endpush

@php
    use App\Support\Catalogs\ModuleCatalog;

    $dailyCards = collect([
        [
            'module' => ModuleCatalog::APPOINTMENTS,
            'can' => $canAccessAppointments,
            'route' => route('appointments.calendar'),
            'title' => 'Agenda',
            'text' => 'Ver calendario mensual y administrar turnos',
            'meta' => 'Calendario operativo',
        ],
        [
            'module' => ModuleCatalog::PARTIES,
            'can' => $canAccessParties,
            'route' => route('parties.index'),
            'title' => 'Contactos',
            'text' => 'Ver y administrar contactos',
            'meta' => $partiesCount . ' contactos',
        ],
        [
            'module' => ModuleCatalog::ASSETS,
            'can' => $canAccessAssets,
            'route' => route('assets.index'),
            'title' => 'Activos',
            'text' => 'Ver y administrar activos operativos',
            'meta' => $assetsCount . ' activos',
        ],
    ])->where('can', true)->values();

    $managementCards = collect([
        [
            'module' => ModuleCatalog::ORDERS,
            'can' => $canAccessOrders,
            'route' => route('orders.index'),
            'title' => 'Órdenes',
            'text' => 'Ver y administrar órdenes',
            'meta' => $ordersCount . ' órdenes',
        ],
        [
            'module' => ModuleCatalog::TASKS,
            'can' => $canAccessTasks,
            'route' => route('tasks.index'),
            'title' => 'Tareas',
            'text' => 'Ver y administrar tareas',
            'meta' => 'Trabajo diario',
        ],
        [
            'module' => ModuleCatalog::PROJECTS,
            'can' => $canAccessProjects,
            'route' => route('projects.index'),
            'title' => 'Proyectos',
            'text' => 'Ver y administrar proyectos',
            'meta' => 'Seguimiento operativo',
        ],
        [
            'module' => ModuleCatalog::PRODUCTS,
            'can' => $canAccessProducts,
            'route' => route('products.index'),
            'title' => 'Productos',
            'text' => 'Ver y administrar productos y servicios',
            'meta' => $productsCount . ' productos',
        ],
        [
            'module' => ModuleCatalog::INVENTORY,
            'can' => $canAccessInventory,
            'route' => route('inventory.index'),
            'title' => 'Inventario',
            'text' => 'Ver saldos por producto y abrir fichas operativas',
            'meta' => 'Stock y movimientos',
        ],
        [
            'module' => ModuleCatalog::DOCUMENTS,
            'can' => $canAccessDocuments,
            'route' => route('documents.index'),
            'title' => 'Documentos',
            'text' => 'Ver y administrar documentos comerciales',
            'meta' => $documentsCount . ' documentos',
        ],
    ])->where('can', true)->values();
@endphp

@section('content')
    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio']]" />

        <x-page-header title="Dashboard" />

        @if ($dailyCards->isNotEmpty())
            <x-card>
                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Operación diaria</h2>
                    <p class="dashboard-section-text">Accesos principales para el trabajo cotidiano.</p>
                </div>

                <div class="dashboard-grid dashboard-grid--premium">
                    @foreach ($dailyCards as $card)
                        @php
                            $icon = ModuleCatalog::icon($card['module']);
                        @endphp

                        <a href="{{ $card['route'] }}"
                            class="dashboard-link-card dashboard-module-card dashboard-module-card--{{ $card['module'] }}">
                            <span class="dashboard-module-icon">
                                <x-dynamic-component :component="'icons.' . $icon" />
                            </span>

                            <span class="dashboard-module-watermark">
                                <x-dynamic-component :component="'icons.' . $icon" />
                            </span>

                            <span class="dashboard-link-title">{{ $card['title'] }}</span>
                            <span class="dashboard-link-text">{{ $card['text'] }}</span>
                            <span class="dashboard-link-meta">{{ $card['meta'] }}</span>
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif

        @if ($managementCards->isNotEmpty())
            <x-card>
                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Gestión complementaria</h2>
                    <p class="dashboard-section-text">Módulos de seguimiento interno, planificación y soporte.</p>
                </div>

                <div class="dashboard-grid dashboard-grid--premium">
                    @foreach ($managementCards as $card)
                        @php
                            $icon = ModuleCatalog::icon($card['module']);
                        @endphp

                        <a href="{{ $card['route'] }}"
                            class="dashboard-link-card dashboard-module-card dashboard-module-card--{{ $card['module'] }}">
                            <span class="dashboard-module-icon">
                                <x-dynamic-component :component="'icons.' . $icon" />
                            </span>

                            <span class="dashboard-module-watermark">
                                <x-dynamic-component :component="'icons.' . $icon" />
                            </span>

                            <span class="dashboard-link-title">{{ $card['title'] }}</span>
                            <span class="dashboard-link-text">{{ $card['text'] }}</span>
                            <span class="dashboard-link-meta">{{ $card['meta'] }}</span>
                        </a>
                    @endforeach
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