{{-- FILE: resources/views/tenants/partials/operational-activity-table.blade.php | V4 --}}

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
                            $changeDetails = collect($row['change_details'] ?? []);
                            $changeModalId = 'operational-activity-changes-' . $row['id'];
                        @endphp

                        <tr>
                            <td>
                                {{ $row['occurred_at']?->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            <td>
                                {{ $moduleLabel }}
                            </td>

                            <td>
                                <div>
                                    {{ $typeLabel }}
                                </div>

                                @if (filled($row['change_summary'] ?? null))
                                    <div class="form-help" style="margin-top: .25rem;">
                                        {{ $row['change_summary'] }}
                                    </div>
                                @endif

                                @if ($changeDetails->isNotEmpty())
                                    <div style="margin-top: .35rem;">
                                        <x-button-secondary
                                            type="button"
                                            class="btn-icon"
                                            title="Ver cambios"
                                            label="Ver cambios"
                                            data-action="app-modal-open"
                                            data-modal-target="#{{ $changeModalId }}"
                                        >
                                            <x-icons.changes />
                                        </x-button-secondary>
                                    </div>
                                @endif
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

        @foreach ($operationalActivityRows as $row)
            @php
                $changeDetails = collect($row['change_details'] ?? []);
                $changeModalId = 'operational-activity-changes-' . $row['id'];
            @endphp

            @if ($changeDetails->isNotEmpty())
                <x-modal :id="$changeModalId" title="Detalle de cambios" size="lg">
                    <div class="dashboard-section-header">
                        <h3 class="dashboard-section-title">
                            {{ $row['record_label'] }}
                        </h3>

                        <p class="dashboard-section-text">
                            {{ $row['occurred_at']?->format('d/m/Y H:i') ?? '—' }}
                        </p>
                    </div>

                    @foreach ($changeDetails as $change)
                        <div class="detail-block" style="margin-bottom: .75rem;">
                            <span class="detail-block-label">
                                {{ $change['label'] }}
                            </span>

                            <div class="detail-block-value">
                                <strong>Antes</strong>
                                <div style="white-space: pre-wrap;">{{ $change['from'] }}</div>
                            </div>

                            <div class="detail-block-value" style="margin-top: .5rem;">
                                <strong>Después</strong>
                                <div style="white-space: pre-wrap;">{{ $change['to'] }}</div>
                            </div>
                        </div>
                    @endforeach

                    <x-slot:footer>
                        <x-button-secondary
                            type="button"
                            data-action="app-modal-close"
                            data-modal-target="#{{ $changeModalId }}"
                        >
                            Cerrar
                        </x-button-secondary>
                    </x-slot:footer>
                </x-modal>
            @endif
        @endforeach
    @endif
</x-card>