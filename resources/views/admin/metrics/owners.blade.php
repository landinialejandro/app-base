{{-- FILE: resources/views/admin/metrics/owners.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Owners activos')

@section('content')
    <x-page class="list-page">
        <x-breadcrumb :items="[['label' => 'Administración', 'url' => route('admin.dashboard')], ['label' => 'Owners activos']]" />

        <x-page-header title="Owners activos">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Volver al panel</a>
        </x-page-header>

        <x-card class="mb-4">
            <p class="mb-0">
                Owners con membresías activas en tenants del sistema.
            </p>
        </x-card>

        <x-card class="list-card">
            @if ($owners->isEmpty())
                <p class="mb-0">No hay owners activos registrados.</p>
            @else
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Empresas</th>
                                <th>Cantidad de empresas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($owners as $owner)
                                <tr>
                                    <td>{{ $owner->name }}</td>
                                    <td>{{ $owner->email }}</td>
                                    <td>
                                        @foreach ($owner->memberships as $membership)
                                            @if ($membership->tenant)
                                                <div>
                                                    <a href="{{ route('admin.tenants.show', $membership->tenant) }}">
                                                        {{ $membership->tenant->name }}
                                                    </a>
                                                </div>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td>{{ $owner->memberships->count() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $owners->links() }}
            @endif
        </x-card>
    </x-page>
@endsection
