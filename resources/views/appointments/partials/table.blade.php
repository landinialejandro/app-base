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
                    <th>{{ AppointmentCatalog::contactLabel() }}</th>
                    <th>{{ AppointmentCatalog::assetLabel() }}</th>
                    <th>Cuándo</th>
                    <th>{{ AppointmentCatalog::assignedUserLabel() }}</th>
                    <th>{{ AppointmentCatalog::orderLabel() }}</th>
                    <th>Estado operativo</th>
                    <th>{{ AppointmentCatalog::workPlaceLabel() }}</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($appointments as $appointment)
                    @php
                        $rowTitle = AppointmentCatalog::rowTitleFor($appointment->kind, $appointment->work_mode);
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
                        <td>{{ $appointment->workstation_name ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
