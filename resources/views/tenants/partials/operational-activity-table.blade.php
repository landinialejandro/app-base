{{-- FILE: resources/views/tenants/partials/operational-activity-table.blade.php | V1 --}}

@php
    use App\Support\Catalogs\ModuleCatalog;

    $operationalActivityRows = collect($operationalActivityRows ?? []);

    $title = $title ?? 'Actividad operativa';
    $description = $description ?? 'Registro reciente de actividad operativa asociada a este recurso.';
    $emptyLabel = $emptyLabel ?? 'Sin actividad registrada';
    $emptyMessage = $emptyMessage ?? 'Todavía no hay actividad operativa registrada para este recurso.';

    $activityTypeLabels = [
        'created' => 'Creación',
        'updated' => 'Actualización',
        'assigned' => 'Asignación',
        'reassigned' => 'Reasignación',
        'unassigned' => 'Desasignación',
        'status_changed' => 'Cambio de estado',
    ];
@endphp

<x-card>
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">{{ $title }}</h2>

        @if (filled($description))
            <p class="dashboard-section-text">
                {{ $description }}
            </p>
        @endif
    </div>

    <div class="form-help" style="margin-bottom: 1rem;">
        Esta lectura es informativa y no habilita acciones sobre los registros originales.
    </div>

    @if ($operationalActivityRows->isEmpty())
        <div class="detail-block">
            <span class="detail-block-label">{{ $emptyLabel }}</span>
            <div class="detail-block-value">
                {{ $emptyMessage }}
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Módulo</th>
                        <th>Actividad</th>
                        <th>Registro</th>
                        <th>Actor</th>
                        <th>Sujeto</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($operationalActivityRows as $row)
                        @php
                            $moduleLabel = ModuleCatalog::label($row['module'], $row['module']);
                            $typeLabel = $activityTypeLabels[$row['activity_type']] ?? $row['activity_type'];
                        @endphp

                        <tr>
                            <td>
                                {{ $row['occurred_at']?->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            <td>
                                {{ $moduleLabel }}
                            </td>

                            <td>
                                {{ $typeLabel }}
                            </td>

                            <td>
                                @if ($row['record_url'])
                                    <a href="{{ $row['record_url'] }}">
                                        {{ $row['record_label'] }}
                                    </a>
                                @else
                                    {{ $row['record_label'] }}
                                @endif
                            </td>

                            <td>
                                {{ $row['actor_label'] }}
                            </td>

                            <td>
                                {{ $row['subject_label'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>