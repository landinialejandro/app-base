{{-- FILE: resources/views/tenants/partials/profile-activity-tab.blade.php | V3 --}}

@php
    use App\Support\Catalogs\ModuleCatalog;

    $operationalActivityRows = $operationalActivityRows ?? collect();

    $activityTypeLabels = [
        'created' => 'Creación',
        'updated' => 'Actualización',
        'assigned' => 'Asignación',
        'reassigned' => 'Reasignación',
        'unassigned' => 'Desasignación',
        'status_changed' => 'Cambio de estado',
    ];
@endphp

<section class="tab-panel {{ $activeTab === 'activity' ? 'is-active' : '' }}" data-tab-panel="activity"
    {{ $activeTab === 'activity' ? '' : 'hidden' }}>
    <div class="tab-panel-stack">
        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Actividad operativa</h2>
                <p class="dashboard-section-text">
                    Registro reciente de acciones operativas realizadas dentro de la empresa.
                </p>
            </div>

            <div class="form-help" style="margin-bottom: 1rem;">
                Se muestran los últimos 50 registros. Esta lectura es informativa y no habilita acciones sobre los
                registros originales.
            </div>

            @if ($operationalActivityRows->isEmpty())
                <div class="detail-block">
                    <span class="detail-block-label">Sin actividad registrada</span>
                    <div class="detail-block-value">
                        Todavía no hay actividad operativa registrada para esta empresa.
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
    </div>
</section>