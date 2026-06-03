{{-- FILE: resources/views/dashboard.blade.php | V13 --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/modules/dashboard.css') }}">
@endpush

@section('content')
    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio']]" />

        <x-page-header title="Dashboard" />

        @if ($dailyCards->isNotEmpty())
            <x-card>
                <div class="content-section-header">
                    <h2 class="content-section-title">Operación diaria</h2>
                    <p class="content-section-text">Accesos principales para el trabajo cotidiano.</p>
                </div>

                <div class="dashboard-grid dashboard-grid--premium">
                    @foreach ($dailyCards as $card)
                        <a href="{{ $card['route'] }}"
                            class="dashboard-link-card dashboard-module-card dashboard-module-card--{{ $card['module'] }}">
                            <span class="dashboard-module-icon">
                                <x-dynamic-component :component="'icons.' . $card['icon']" />
                            </span>

                            <span class="dashboard-module-watermark">
                                <x-dynamic-component :component="'icons.' . $card['icon']" />
                            </span>

                            <span class="dashboard-link-title">{{ $card['title'] }}</span>
                            <span class="dashboard-link-text">{{ $card['text'] }}</span>
                            <span class="dashboard-link-meta">{{ $card['meta'] }}</span>
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif

        @if ($serviceMaintenanceCards->isNotEmpty())
            <x-card>
                <div class="content-section-header">
                    <h2 class="content-section-title">Servicio y mantenimiento</h2>
                    <p class="content-section-text">
                        Accesos automatizados para trabajos técnicos, servicios y mantenimiento. Las acciones disponibles
                        dependen de los permisos configurados para órdenes de servicio.
                    </p>
                </div>

                <div class="dashboard-grid dashboard-grid--premium">
                    @foreach ($serviceMaintenanceCards as $card)
                        <a href="{{ $card['route'] }}"
                            class="dashboard-link-card dashboard-module-card dashboard-module-card--{{ $card['module'] }}">
                            <span class="dashboard-module-icon">
                                <x-dynamic-component :component="'icons.' . $card['icon']" />
                            </span>

                            <span class="dashboard-module-watermark">
                                <x-dynamic-component :component="'icons.' . $card['icon']" />
                            </span>

                            <span class="dashboard-link-title">{{ $card['title'] }}</span>
                            <span class="dashboard-link-text">{{ $card['text'] }}</span>
                            <span class="dashboard-link-meta">{{ $card['meta'] }}</span>
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif

        @if ($productionCards->isNotEmpty())
            <x-card>
                <div class="content-section-header">
                    <h2 class="content-section-title">Producción</h2>
                    <p class="content-section-text">
                        Acceso operativo a órdenes de producción, recetas y contrato material. Las órdenes siguen siendo
                        gestionadas por Orders, con materiales desde Inventory y composición desde Products.
                    </p>
                </div>

                <div class="dashboard-grid dashboard-grid--premium">
                    @foreach ($productionCards as $card)
                        <a href="{{ $card['route'] }}"
                            class="dashboard-link-card dashboard-module-card dashboard-module-card--{{ $card['module'] }}">
                            <span class="dashboard-module-icon">
                                <x-dynamic-component :component="'icons.' . $card['icon']" />
                            </span>

                            <span class="dashboard-module-watermark">
                                <x-dynamic-component :component="'icons.' . $card['icon']" />
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
                <div class="content-section-header">
                    <h2 class="content-section-title">Gestión complementaria</h2>
                    <p class="content-section-text">Módulos de seguimiento interno, planificación y soporte.</p>
                </div>

                <div class="dashboard-grid dashboard-grid--premium">
                    @foreach ($managementCards as $card)
                        <a href="{{ $card['route'] }}"
                            class="dashboard-link-card dashboard-module-card dashboard-module-card--{{ $card['module'] }}">
                            <span class="dashboard-module-icon">
                                <x-dynamic-component :component="'icons.' . $card['icon']" />
                            </span>

                            <span class="dashboard-module-watermark">
                                <x-dynamic-component :component="'icons.' . $card['icon']" />
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

        <x-dev-component-version name="dashboard" version="V13" align="right" />
    </x-page>
@endsection