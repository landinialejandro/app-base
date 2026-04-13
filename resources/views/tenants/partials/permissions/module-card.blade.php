{{-- FILE: resources/views/tenants/partials/permissions/module-card.blade.php | V6 --}}

@php
    $collapsed = $collapsed ?? !$loop->first;

    $moduleHelp = match ($module) {
        'dashboard' => 'Define si este perfil puede acceder al panel principal.',
        default => 'Define qué podrá hacer este tipo de acceso en este módulo.',
    };

    $moduleDetail = match ($module) {
        'dashboard' => 'Este módulo solo administra acceso al panel y visualización general.',
        default => 'Puedes permitir acciones como ver, crear o editar información.
Si tienes dudas, conviene dar menos acceso y ampliarlo después.',
    };
@endphp

<x-card :collapsible="true" :collapsed="$collapsed" data-module-card data-module="{{ $module }}">
    <x-slot:header>
        <div>
            <h2 class="card-title">{{ $moduleLabel }}</h2>

            <p class="card-subtitle">
                {{ $moduleHelp }}
            </p>

            <div class="form-help">
                {{ $moduleDetail }}
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
                    <th>Acción</th>
                    <th>Permitir</th>
                    <th>Sobre qué información</th>
                    <th>Cómo se aplica</th>
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
