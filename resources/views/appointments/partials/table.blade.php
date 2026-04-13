{{-- FILE: resources/views/appointments/partials/table.blade.php | V9 --}}

@php
    use App\Support\Catalogs\AppointmentCatalog;
    use App\Support\Navigation\AppointmentNavigationTrail;
    use App\Support\Navigation\NavigationTrail;
    use App\Support\Orders\OrderLinkedAction;
    use App\Support\Parties\PartyLinkedAction;

    $appointments = $appointments ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay turnos para mostrar.';
    $supportsPartiesModule = $supportsPartiesModule ?? false;
    $supportsAssetsModule = $supportsAssetsModule ?? false;
    $supportsOrdersModule = $supportsOrdersModule ?? false;
@endphp

@if ($appointments->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Turno</th>
                    <th>{{ AppointmentCatalog::contactLabel() }}</th>
                    @if ($supportsAssetsModule)
                        <th>{{ AppointmentCatalog::assetLabel() }}</th>
                    @endif
                    <th>Cuándo</th>
                    <th>{{ AppointmentCatalog::assignedUserLabel() }}</th>
                    @if ($supportsOrdersModule)
                        <th>{{ AppointmentCatalog::orderLabel() }}</th>
                    @endif
                    <th>Estado operativo</th>
                    <th>{{ AppointmentCatalog::workPlaceLabel() }}</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($appointments as $appointment)
                    @php
                        $rowTitle = AppointmentCatalog::rowTitleFor($appointment->kind, $appointment->work_mode);
                        $appointmentTrail = AppointmentNavigationTrail::base($appointment);
                        $appointmentTrailQuery = NavigationTrail::toQuery($appointmentTrail);

                        $orderAction = OrderLinkedAction::forAppointment($appointment, $appointmentTrailQuery, false);
                        $partyAction = PartyLinkedAction::forParty(
                            $appointment->party,
                            $appointmentTrailQuery,
                            AppointmentCatalog::contactLabel(),
                        );
                    @endphp

                    <tr>
                        <td>
                            <a
                                href="{{ route('appointments.show', ['appointment' => $appointment] + $appointmentTrailQuery) }}">
                                {{ $rowTitle }}
                            </a>
                            <div class="text-muted">#{{ $appointment->id }}</div>
                        </td>

                        <td>
                            @include('parties.components.linked-party-action', [
                                'action' => $partyAction,
                                'variant' => 'inline',
                            ])
                        </td>

                        @if ($supportsAssetsModule)
                            <td>
                                @if ($appointment->asset)
                                    @can('view', $appointment->asset)
                                        <a
                                            href="{{ route('assets.show', ['asset' => $appointment->asset] + $appointmentTrailQuery) }}">
                                            {{ $appointment->asset->name }}
                                        </a>
                                    @else
                                        {{ $appointment->asset->name }}
                                    @endcan
                                @else
                                    —
                                @endif
                            </td>
                        @endif

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

                        @if ($supportsOrdersModule)
                            <td>
                                @include('orders.components.linked-order-action', [
                                    'action' => $orderAction,
                                    'variant' => 'inline',
                                ])
                            </td>
                        @endif

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
