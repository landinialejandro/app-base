{{-- FILE: resources/views/self-service-sales/store-selector.blade.php | V6 --}}

@extends('layouts.app')

@php($publicPage = true)

@section('title', 'Seleccionar tienda')

@section('content')
    <x-page>
        <x-card>
            <div class="dashboard-section-header">
                <h1 class="dashboard-section-title">Seleccionar tienda</h1>

                @if(! $hasToken)
                    <p class="dashboard-section-text">
                        Este espacio estará disponible para elegir una tienda vinculada a tu cuenta externa.
                    </p>

                    <p class="dashboard-section-text">
                        La selección de tienda todavía no está habilitada. Iniciá nuevamente el acceso desde Tienda.
                    </p>
                @elseif(! $selectionToken)
                    <p class="dashboard-section-text">
                        No pudimos continuar con la selección de tienda.
                    </p>

                    <p class="dashboard-section-text">
                        El enlace puede haber vencido o ya no estar disponible. Iniciá nuevamente el acceso desde Tienda.
                    </p>
                @elseif($storeSelectorRows->isEmpty())
                    <p class="dashboard-section-text">
                        No encontramos tiendas disponibles para seleccionar.
                    </p>

                    <p class="dashboard-section-text">
                        Iniciá nuevamente el acceso desde Tienda o registrate en la tienda de la empresa correspondiente.
                    </p>
                @else
                    <p class="dashboard-section-text">
                        Elegí la tienda a la que querés ingresar.
                    </p>

                    <p class="dashboard-section-text">
                        Esta selección todavía no inicia operación comercial. Si tu identidad operativa no está completa,
                        la tienda podrá solicitar datos adicionales antes de permitir compras o servicios.
                    </p>
                @endif
            </div>

            @if($storeSelectorRows->isNotEmpty())
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tienda</th>
                                <th>Cliente</th>
                                <th>Identidad</th>
                                <th>Operación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($storeSelectorRows as $storeSelectorRow)
                                <tr>
                                    <td>{{ $storeSelectorRow['tenant_label'] }}</td>
                                    <td>{{ $storeSelectorRow['party_label'] }}</td>
                                    <td>{{ $storeSelectorRow['identity_label'] }}</td>
                                    <td>
                                        <span class="status-badge {{ $storeSelectorRow['operation_enabled'] ? 'status-badge--done' : 'status-badge--pending' }}">
                                            {{ $storeSelectorRow['operation_enabled'] ? 'Habilitada' : 'Pendiente' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="form-actions">
                <a href="{{ route('self_service_sales.access') }}" class="btn btn-secondary">
                    Volver a Tienda
                </a>
            </div>

            <x-dev-component-version name="self-service-sales.store-selector" version="V6" />
        </x-card>
    </x-page>
@endsection