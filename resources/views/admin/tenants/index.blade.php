{{-- FILE: resources/views/admin/tenants/index.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Tenants')

@section('content')
    <x-page class="list-page">
        <x-breadcrumb :items="[['label' => 'Administración', 'url' => route('admin.dashboard')], ['label' => 'Tenants']]" />

        <x-page-header title="Tenants">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Volver al panel</a>
        </x-page-header>

        <x-list-filters-card :action="route('admin.tenants.index')">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ $search }}"
                            placeholder="Nombre, slug o ID">
                    </div>
                </div>
            </x-slot:primary>
        </x-list-filters-card>

        <x-card class="list-card">
            @if ($tenants->isEmpty())
                <p class="mb-0">No se encontraron tenants para el criterio indicado.</p>
            @else
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Slug</th>
                                <th>ID</th>
                                <th>Usuarios</th>
                                <th>Owners</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tenants as $tenant)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.tenants.show', $tenant) }}">
                                            {{ $tenant->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.tenants.show', $tenant) }}">
                                            {{ $tenant->slug }}
                                        </a>
                                    </td>
                                    <td>{{ $tenant->id }}</td>
                                    <td>{{ $tenant->users_count }}</td>
                                    <td>{{ $tenant->owners_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $tenants->links() }}
            @endif
        </x-card>
    </x-page>
@endsection
