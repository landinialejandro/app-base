{{-- FILE: resources/views/service/dashboard.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Servicio y mantenimiento')

@section('content')
    <x-page class="dashboard-page">
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Servicio y mantenimiento'],
        ]" />

        <x-page-header title="Servicio y mantenimiento" />

        <x-card>
            <div class="dashboard-card-content">
                <div class="detail-block">
                    <h2 class="dashboard-section-title">Órdenes de servicio</h2>

                    <p class="form-help">
                        Acceso operativo a las órdenes de servicio gestionadas desde Orders dentro del universo Servicio y mantenimiento.
                    </p>

                    @if (!is_null($serviceOrdersCount))
                        <p class="form-help">
                            Órdenes registradas: {{ $serviceOrdersCount }}
                        </p>
                    @endif
                </div>

                <div class="form-actions">
                    @if ($canViewServiceOrders)
                        <a href="{{ route('service.orders.index') }}" class="btn btn-primary">
                            Ver órdenes de servicio
                        </a>
                    @endif

                    @if ($canCreateServiceOrders)
                        <a href="{{ route('service.orders.create') }}" class="btn btn-secondary">
                            Nueva orden de servicio
                        </a>
                    @endif
                </div>

                @if (!$canViewServiceOrders && !$canCreateServiceOrders)
                    <div class="detail-block">
                        <p class="form-help">
                            El área está disponible, pero no hay permisos operativos habilitados para consultar o crear órdenes de servicio.
                        </p>
                    </div>
                @endif
            </div>
        </x-card>
    </x-page>

    <x-dev-component-version name="service.dashboard" version="V2" align="right" />
@endsection