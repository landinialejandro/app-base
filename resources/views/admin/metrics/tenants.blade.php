{{-- FILE: resources/views/admin/metrics/tenants.blade.php --}}

@extends('layouts.app')

@section('title', 'Tenants')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Administración', 'url' => route('admin.dashboard')],
            ['label' => 'Tenants'],
        ]" />

        <x-page-header title="Tenants">
            <div class="page-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                    Volver
                </a>
            </div>
        </x-page-header>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Tenants del sistema</h2>
                <p class="dashboard-section-text">
                    Resumen de empresas con cantidad de usuarios y owners asociados.
                </p>
            </div>

            @if ($tenants->count())
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Slug</th>
                                <th>Usuarios</th>
                                <th>Owners</th>
                                <th>Creación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tenants as $tenant)
                                <tr>
                                    <td>{{ $tenant->name }}</td>
                                    <td>{{ $tenant->slug }}</td>
                                    <td>{{ $tenant->users_count }}</td>
                                    <td>{{ $tenant->owners_count }}</td>
                                    <td>{{ $tenant->created_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $tenants->links() }}
                </div>
            @else
                <p class="mb-0">No hay tenants registrados.</p>
            @endif
        </x-card>
    </x-page>
@endsection