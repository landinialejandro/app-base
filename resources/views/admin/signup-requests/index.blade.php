{{-- FILE: resources/views/admin/signup-requests/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Solicitudes pendientes')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Administración', 'url' => route('admin.dashboard')],
            ['label' => 'Solicitudes pendientes'],
        ]" />

        <x-page-header title="Solicitudes pendientes">
            <div class="page-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                    Volver
                </a>
            </div>
        </x-page-header>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Bandeja de revisión</h2>
                <p class="dashboard-section-text">
                    Solicitudes públicas pendientes de aprobación o rechazo.
                </p>
            </div>

            @if ($signupRequests->count())
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Empresa</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th class="compact-actions-cell"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($signupRequests as $request)
                                <tr>
                                    <td>{{ $request->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $request->requested_name }}</td>
                                    <td>{{ $request->requested_email }}</td>
                                    <td>{{ $request->company_name }}</td>
                                    <td>{{ $request->phone_whatsapp }}</td>
                                    <td>{{ $request->status }}</td>
                                    <td class="compact-actions-cell">
                                        <a href="{{ route('admin.signup-requests.show', $request) }}"
                                            class="btn btn-secondary btn-icon" title="Ver detalle" aria-label="Ver detalle">
                                            <x-icons.eye />
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $signupRequests->links() }}
                </div>
            @else
                <p class="mb-0">No hay solicitudes pendientes.</p>
            @endif
        </x-card>
    </x-page>
@endsection
