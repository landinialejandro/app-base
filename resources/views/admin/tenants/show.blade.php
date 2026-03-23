{{-- FILE: resources/views/admin/tenants/show.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Tenant')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('admin.dashboard')],
            ['label' => 'Tenants', 'url' => route('admin.tenants.index')],
            ['label' => $tenant->name],
        ]" />

        <x-page-header title="Tenant">
            <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">Volver al listado</a>
            <a href="{{ route('admin.tenants.modules.edit', $tenant) }}" class="btn btn-primary">Configurar módulos</a>
        </x-page-header>

        <div class="summary-inline-grid">
            <div class="summary-inline-card">
                <div class="summary-inline-label">Empresa</div>
                <div class="summary-inline-value">{{ $tenant->name }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Slug</div>
                <div class="summary-inline-value">{{ $tenant->slug }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">ID</div>
                <div class="summary-inline-value">{{ $tenant->id }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Usuarios</div>
                <div class="summary-inline-value">{{ $tenant->users_count }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Owners</div>
                <div class="summary-inline-value">{{ $tenant->owners_count }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Módulos habilitados</div>
                <div class="summary-inline-value">{{ $enabledModulesCount }}</div>
            </div>
        </div>

        <x-card class="mb-4">
            <h2 class="card-title">Estado de configuración</h2>

            <p>
                @if ($hasEditableOverride)
                    Este tenant tiene una configuración editable persistida en
                    <code>settings.module_access.enabled_modules</code>.
                @else
                    Este tenant no tiene override editable guardado. Actualmente hereda la configuración base efectiva.
                @endif
            </p>
        </x-card>

        <div class="table-card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Módulo</th>
                        <th>Slug</th>
                        <th>Override editable</th>
                        <th>Estado efectivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($moduleLabels as $module => $label)
                        @php
                            $hasOverrideValue = array_key_exists($module, $editableOverrides);
                            $overrideValue = $hasOverrideValue ? $editableOverrides[$module] : null;
                            $effectiveValue = (bool) ($effectiveModules[$module] ?? false);
                        @endphp

                        <tr>
                            <td>{{ $label }}</td>
                            <td><code>{{ $module }}</code></td>
                            <td>
                                @if ($hasOverrideValue)
                                    {{ $overrideValue ? 'Habilitado' : 'Deshabilitado' }}
                                @else
                                    Heredado
                                @endif
                            </td>
                            <td>{{ $effectiveValue ? 'Habilitado' : 'Deshabilitado' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($enabledModulesCount === 0)
            <x-card class="mt-4">
                <p>
                    Atención: este tenant quedó actualmente sin módulos habilitados de forma efectiva.
                </p>
            </x-card>
        @endif
    </x-page>
@endsection
