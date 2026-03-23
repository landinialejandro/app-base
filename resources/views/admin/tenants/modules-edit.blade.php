{{-- FILE: resources/views/admin/tenants/modules-edit.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Configurar módulos')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('admin.dashboard')],
            ['label' => 'Tenants', 'url' => route('admin.tenants.index')],
            ['label' => $tenant->name, 'url' => route('admin.tenants.show', $tenant)],
            ['label' => 'Configurar módulos'],
        ]" />

        <x-page-header title="Configurar módulos">
            <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-secondary">Volver al tenant</a>
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
                <div class="summary-inline-label">Módulos efectivos activos</div>
                <div class="summary-inline-value">{{ $enabledModulesCount }}</div>
            </div>
        </div>

        <x-card class="mb-4">
            <p>
                Esta pantalla guarda un override editable en <code>settings.module_access.enabled_modules</code>.
            </p>

            <p>
                El valor mostrado como “estado efectivo” ya contempla la resolución real del sistema.
            </p>

            @if (!$hasEditableOverride)
                <p>
                    Actualmente este tenant está heredando configuración. Al guardar, se generará un override explícito.
                </p>
            @endif
        </x-card>

        <form method="POST" action="{{ route('admin.tenants.modules.update', $tenant) }}" class="form">
            @csrf
            @method('PUT')

            <div class="table-card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Módulo</th>
                            <th>Slug</th>
                            <th>Habilitado</th>
                            <th>Estado efectivo actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($moduleLabels as $module => $label)
                            @php
                                $checked = array_key_exists($module, $editableOverrides)
                                    ? (bool) $editableOverrides[$module]
                                    : (bool) ($effectiveModules[$module] ?? false);

                                $effectiveValue = (bool) ($effectiveModules[$module] ?? false);
                            @endphp

                            <tr>
                                <td>{{ $label }}</td>
                                <td><code>{{ $module }}</code></td>
                                <td>
                                    <label>
                                        <input type="checkbox" name="modules[{{ $module }}]" value="1"
                                            {{ old('modules.' . $module, $checked) ? 'checked' : '' }}>
                                        Habilitado
                                    </label>
                                </td>
                                <td>{{ $effectiveValue ? 'Habilitado' : 'Deshabilitado' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary">Guardar configuración</button>
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>

        <x-card class="mt-4">
            <h2 class="card-title">Restaurar configuración heredada</h2>

            <p>
                Esta acción elimina el override editable persistido para este tenant y vuelve a aplicar la configuración
                heredada.
            </p>

            <form method="POST" action="{{ route('admin.tenants.modules.reset', $tenant) }}">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-secondary">
                    Restaurar valores heredados
                </button>
            </form>
        </x-card>
    </x-page>
@endsection
