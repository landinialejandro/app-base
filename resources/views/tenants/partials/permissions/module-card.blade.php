{{-- FILE: resources/views/tenants/partials/permissions/module-card.blade.php | V3 --}}

<x-card>
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">{{ $moduleLabel }}</h2>
        <p class="dashboard-section-text">
            Configuración base del rol para este módulo.
        </p>
    </div>

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
