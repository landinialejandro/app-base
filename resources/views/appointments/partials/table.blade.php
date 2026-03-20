@php
    use App\Support\Catalogs\AppointmentCatalog;

    $appointments = $appointments ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay turnos para mostrar.';
@endphp

@if ($appointments->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Turno</th>
                    <th>A quién</th>
                    <th>Qué voy a ver</th>
                    <th>Cuándo</th>
                    <th>Quién lo realiza</th>
                    <th>Orden</th>
                    <th>Estado operativo</th>
                    <th>Lugar de trabajo</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($appointments as $appointment)
                    @php
                        $rowTitle = match (true) {
                            $appointment->kind === AppointmentCatalog::KIND_BLOCK => 'Bloqueo de agenda',
                            $appointment->kind === AppointmentCatalog::KIND_VISIT => 'Turno de visita',
                            $appointment->work_mode === AppointmentCatalog::WORK_MODE_FIELD_ASSISTANCE
                                => 'Turno de asistencia externa',
                            default => 'Turno de taller',
                        };

                        $referenceLabel = AppointmentCatalog::referenceLabelForKind($appointment->kind);
                    @endphp

                    <tr>
                        <td>
                            <a href="{{ route('appointments.show', $appointment) }}">
                                {{ $rowTitle }}
                            </a>
                            <div class="text-muted">#{{ $appointment->id }}</div>
                        </td>

                        <td>
                            @if ($appointment->party)
                                <a href="{{ route('parties.show', $appointment->party) }}">
                                    {{ $appointment->party->name }}
                                </a>
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @if ($appointment->asset)
                                <a href="{{ route('assets.show', $appointment->asset) }}">
                                    {{ $appointment->asset->name }}
                                </a>
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @if ($appointment->is_all_day)
                                {{ $appointment->scheduled_date?->format('d/m/Y') ?: '—' }} · Día completo
                            @elseif ($appointment->starts_at && $appointment->ends_at)
                                {{ $appointment->scheduled_date?->format('d/m/Y') ?: '—' }}
                                ·
                                {{ $appointment->starts_at->format('H:i') }} -
                                {{ $appointment->ends_at->format('H:i') }}
                            @else
                                {{ $appointment->scheduled_date?->format('d/m/Y') ?: '—' }} · Sin horario
                            @endif
                        </td>

                        <td>{{ $appointment->assignedUser?->name ?? '—' }}</td>

                        <td>
                            @if ($appointment->order)
                                <a href="{{ route('orders.show', $appointment->order) }}">
                                    {{ $appointment->order->number ?: 'Orden #' . $appointment->order->id }}
                                </a>
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            <span class="status-badge {{ AppointmentCatalog::badgeClass($appointment->status) }}">
                                {{ AppointmentCatalog::statusLabel($appointment->status) }}
                            </span>
                        </td>

                        <td>{{ AppointmentCatalog::workModeLabel($appointment->work_mode) }}</td>

                        <td title="{{ $referenceLabel }}">
                            {{ $appointment->workstation_name ?: '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
