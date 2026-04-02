{{-- FILE: resources/views/tenants/partials/permissions/module-card.blade.php | V4 --}}

@php
    $collapsed = $collapsed ?? !$loop->first;
@endphp

<x-card :collapsible="true" :collapsed="$collapsed" data-module-card data-module="{{ $module }}">
    <x-slot:header>
        <div>
            <h2 class="card-title">{{ $moduleLabel }}</h2>
            <p class="card-subtitle">
                Configuración base del rol para este módulo.
            </p>
            <div class="form-help">
                Activa cada capacidad y, cuando corresponda, define sobre qué registros podrá usarla este rol.
            </div>
        </div>
    </x-slot:header>

    <x-slot:toolbox>
        <button type="button" class="card-tool" data-action="app-card-toggle" aria-label="Expandir o contraer módulo"
            aria-expanded="{{ $collapsed ? 'false' : 'true' }}">
            <span class="icon-expand">
                <x-icons.chevron-down />
            </span>
        </button>
    </x-slot:toolbox>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Capacidad</th>
                    <th>Permitir</th>
                    <th>Alcance</th>
                    <th>Modo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($capabilities as $capability => $meta)
                    @include('tenants.partials.permissions.capability-row', [
                        'module' => $module,
                        'capability' => $capability,
                        'capabilityLabel' => $capabilityLabels[$capability] ?? $capability,
                        'meta' => $meta,
                    ])
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>
