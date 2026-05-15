{{-- FILE: resources/views/production/dashboard.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Producción')

@section('content')
    <x-page class="dashboard-page">
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Producción'],
        ]" />

        <x-page-header title="Producción" />

        <x-card>
            <div class="dashboard-card-content">
                <div class="detail-block">
                    <h2 class="dashboard-section-title">Órdenes de producción</h2>

                    <p class="form-help">
                        Acceso operativo a las órdenes de producción gestionadas desde Orders, con recetas desde Products
                        y contrato material desde Inventory.
                    </p>

                    @if (!is_null($productionOrdersCount))
                        <p class="form-help">
                            Órdenes registradas: {{ $productionOrdersCount }}
                        </p>
                    @endif
                </div>

                <div class="form-actions">
                    @if ($canViewProductionOrders)
                        <a href="{{ route('production.orders.index') }}" class="btn btn-primary">
                            Ver órdenes de producción
                        </a>
                    @endif

                    @if ($canCreateProductionOrders)
                        <a href="{{ route('production.orders.create') }}" class="btn btn-secondary">
                            Nueva orden de producción
                        </a>
                    @endif
                </div>

                @if (!$canViewProductionOrders && !$canCreateProductionOrders)
                    <div class="detail-block">
                        <p class="form-help">
                            El área está disponible, pero no hay permisos operativos habilitados para consultar o crear órdenes de producción.
                        </p>
                    </div>
                @endif
            </div>
        </x-card>
    </x-page>

    <x-dev-component-version name="production.dashboard" version="V1" align="right" />
@endsection