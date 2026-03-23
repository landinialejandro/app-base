{{-- FILE: resources/views/admin/tenants/modules-edit.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Configurar módulos')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Administración', 'url' => route('admin.dashboard')],
            ['label' => 'Tenants', 'url' => route('admin.tenants.index')],
            ['label' => $tenant->name, 'url' => route('admin.tenants.show', $tenant)],
            ['label' => 'Configurar módulos'],
        ]" />

        <x-page-header title="Configurar módulos">
            <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-secondary">Volver</a>
        </x-page-header>

        <x-show-summary details-id="tenant-modules-summary-{{ $tenant->id }}" toggle-label="Más detalle"
            toggle-label-expanded="Ocultar detalle">
            <x-show-summary-item label="Empresa">
                {{ $tenant->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Slug">
                {{ $tenant->slug }}
            </x-show-summary-item>

            <x-show-summary-item label="Módulos efectivos activos">
                {{ $enabledModulesCount }}
            </x-show-summary-item>

            <x-show-summary-item label="Override editable">
                {{ $hasEditableOverride ? 'Sí' : 'No' }}
            </x-show-summary-item>

            <x-slot:details>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Persistencia</span>
                        <div class="detail-block-value">
                            Esta pantalla guarda un override editable en
                            <code>settings.module_access.enabled_modules</code>.
                        </div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Estado efectivo</span>
                        <div class="detail-block-value">
                            El estado efectivo mostrado contempla la resolución real del sistema.
                        </div>
                    </div>
                </div>
            </x-slot:details>
        </x-show-summary>

        @if ($errors->any())
            <x-card class="mb-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        <x-card>
            <form method="POST" action="{{ route('admin.tenants.modules.update', $tenant) }}" class="form">
                @csrf
                @method('PUT')

                <div class="table-wrap list-scroll">
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

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar configuración</button>
                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

        <x-card class="mt-4">
            <p>
                Esta acción elimina el override editable persistido para este tenant y vuelve a aplicar la configuración
                heredada.
            </p>

            <form method="POST" action="{{ route('admin.tenants.modules.reset', $tenant) }}">
                @csrf
                @method('DELETE')

                <div class="form-actions">
                    <button type="submit" class="btn btn-secondary">
                        Restaurar valores heredados
                    </button>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
