{{-- FILE: resources/views/service/dashboard.blade.php | V1 --}}

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
                <h2 class="dashboard-section-title">Órdenes de servicio</h2>

                <p class="form-help">
                    Acceso rápido al flujo operativo de órdenes de servicio.
                </p>

                @if (!is_null($serviceOrdersCount))
                    <p class="form-help">
                        Total actual: {{ $serviceOrdersCount }}
                    </p>
                @endif

                <div class="form-actions">
                    @if ($canViewServiceOrders)
                        <a href="{{ route('service.orders.index') }}" class="btn btn-primary">
                            Ver órdenes
                        </a>
                    @endif

                    @if ($canCreateServiceOrders)
                        <a href="{{ route('service.orders.create') }}" class="btn btn-secondary">
                            Nueva orden
                        </a>
                    @endif
                </div>
            </div>
        </x-card>
    </x-page>

    <x-dev-component-version name="service.dashboard" version="V1" align="right" />
@endsection