{{-- FILE: resources/views/admin/signup-requests/processed.blade.php --}}

@extends('layouts.app')

@section('title', 'Solicitudes procesadas')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Administración', 'url' => route('admin.dashboard')],
            ['label' => 'Solicitudes procesadas'],
        ]" />

        <x-page-header title="Solicitudes procesadas">
            <div class="page-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                    Volver
                </a>
            </div>
        </x-page-header>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Historial de revisión</h2>
                <p class="dashboard-section-text">
                    Solicitudes aprobadas o rechazadas por el superadmin.
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
                <p class="mb-0">No hay solicitudes procesadas.</p>
            @endif
        </x-card>
    </x-page>
@endsection
