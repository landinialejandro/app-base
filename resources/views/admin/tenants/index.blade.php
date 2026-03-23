{{-- FILE: resources/views/admin/tenants/index.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Tenants')

@section('content')
    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('admin.dashboard')], ['label' => 'Tenants']]" />

        <x-page-header title="Tenants">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Volver al panel</a>
        </x-page-header>

        <x-card class="mb-4">
            <form method="GET" action="{{ route('admin.tenants.index') }}" class="form">
                <div class="form-grid form-grid-2">
                    <div class="form-field">
                        <label for="q" class="form-label">Buscar tenant</label>
                        <input id="q" type="text" name="q" value="{{ $search }}" class="form-input"
                            placeholder="Nombre, slug o ID">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </x-card>

        @if ($tenants->isEmpty())
            <x-card>
                <p>No se encontraron tenants para el criterio indicado.</p>
            </x-card>
        @else
            <div class="table-card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Slug</th>
                            <th>ID</th>
                            <th>Usuarios</th>
                            <th>Owners</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tenants as $tenant)
                            <tr>
                                <td>{{ $tenant->name }}</td>
                                <td>{{ $tenant->slug }}</td>
                                <td>{{ $tenant->id }}</td>
                                <td>{{ $tenant->users_count }}</td>
                                <td>{{ $tenant->owners_count }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-secondary">
                                            Ver
                                        </a>

                                        <a href="{{ route('admin.tenants.modules.edit', $tenant) }}"
                                            class="btn btn-primary">
                                            Módulos
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap">
                {{ $tenants->links() }}
            </div>
        @endif
    </x-page>
@endsection
