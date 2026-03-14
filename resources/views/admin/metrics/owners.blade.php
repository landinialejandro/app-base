{{-- FILE: resources/views/admin/metrics/owners.blade.php --}}

@extends('layouts.app')

@section('title', 'Owners activos')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Administración', 'url' => route('admin.dashboard')],
            ['label' => 'Owners activos'],
        ]" />

        <x-page-header title="Owners activos">
            <div class="page-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                    Volver
                </a>
            </div>
        </x-page-header>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Owners del sistema</h2>
                <p class="dashboard-section-text">
                    Usuarios que tienen al menos una membership con rol owner.
                </p>
            </div>

            @if ($owners->count())
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Tenants owner</th>
                                <th>Empresas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($owners as $owner)
                                <tr>
                                    <td>{{ $owner->name }}</td>
                                    <td>{{ $owner->email }}</td>
                                    <td>{{ $owner->owner_tenants_count }}</td>
                                    <td>
                                        @foreach ($owner->memberships as $membership)
                                            @if ($membership->tenant)
                                                <div>{{ $membership->tenant->name }}</div>
                                            @endif
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $owners->links() }}
                </div>
            @else
                <p class="mb-0">No hay owners registrados.</p>
            @endif
        </x-card>
    </x-page>
@endsection