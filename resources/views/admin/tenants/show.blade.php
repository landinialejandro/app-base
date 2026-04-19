{{-- FILE: resources/views/admin/tenants/show.blade.php | V5 --}}

@extends('layouts.app')

@section('title', 'Tenant')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Administración', 'url' => route('admin.dashboard')],
            ['label' => 'Tenants', 'url' => route('admin.metrics.tenants')],
            ['label' => $tenant->name],
        ]" />

        <x-page-header title="Tenant">
            <x-button-edit :href="route('admin.tenants.modules.edit', $tenant)" label="Configurar módulos" />

            <x-button-back :href="route('admin.metrics.tenants')" />
        </x-page-header>

        @if (session('success'))
            <x-card class="mb-4">
                <p class="mb-0">{{ session('success') }}</p>
            </x-card>
        @endif

        <x-show-summary details-id="tenant-summary-{{ $tenant->id }}" toggle-label="Más detalle"
            toggle-label-expanded="Ocultar detalle">
            <x-show-summary-item label="Empresa">
                {{ $tenant->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Slug">
                {{ $tenant->slug }}
            </x-show-summary-item>

            <x-show-summary-item label="Usuarios">
                {{ $tenant->users_count }}
            </x-show-summary-item>

            <x-show-summary-item label="Owners">
                {{ $tenant->owners_count }}
            </x-show-summary-item>

            <x-show-summary-item label="Módulos habilitados">
                {{ $enabledModulesCount }}
            </x-show-summary-item>

            <x-show-summary-item label="Módulos deshabilitados">
                {{ $disabledModulesCount }}
            </x-show-summary-item>

            <x-slot:details>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">ID</span>
                        <div class="detail-block-value">{{ $tenant->id }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Creado</span>
                        <div class="detail-block-value">
                            {{ optional($tenant->created_at)->format('d/m/Y H:i') }}
                        </div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Actualizado</span>
                        <div class="detail-block-value">
                            {{ optional($tenant->updated_at)->format('d/m/Y H:i') }}
                        </div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Configuración editable</span>
                        <div class="detail-block-value">
                            @if ($hasEditableOverride)
                                Este tenant tiene un override persistido en
                                <code>settings.module_access.enabled_modules</code>.
                            @else
                                Este tenant no tiene override editable guardado y actualmente hereda la configuración base.
                            @endif
                        </div>
                    </div>
                </div>
            </x-slot:details>
        </x-show-summary>

        <x-card class="mb-4">
            <p class="mb-0">
                Se muestra el valor persistido editable y el estado efectivo final utilizado por el sistema.
            </p>
        </x-card>

        <x-card>
            <div class="table-wrap list-scroll">
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
        </x-card>

        @if ($enabledModulesCount === 0)
            <x-card class="mt-4">
                <p class="mb-0">
                    Atención: este tenant quedó actualmente sin módulos habilitados de forma efectiva.
                </p>
            </x-card>
        @endif
    </x-page>
@endsection
