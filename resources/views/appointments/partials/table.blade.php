{{-- FILE: resources/views/appointments/partials/table.blade.php | V12 --}}

@php
    use App\Support\Assets\AssetLinked;
    use App\Support\Catalogs\AppointmentCatalog;
    use App\Support\Navigation\NavigationTrail;
    use App\Support\Orders\OrderLinked;
    use App\Support\Parties\PartyLinked;

    $appointments = $appointments ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay turnos para mostrar.';
    $supportsPartiesModule = $supportsPartiesModule ?? false;
    $supportsAssetsModule = $supportsAssetsModule ?? false;
    $supportsOrdersModule = $supportsOrdersModule ?? false;
    $trailQuery = $trailQuery ?? [];
    $containerTrail = NavigationTrail::decode($trailQuery['trail'] ?? null);

    $renderPartyColumn = $supportsPartiesModule;
@endphp

@if ($appointments->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Turno</th>
                    @if ($renderPartyColumn)
                        <th>{{ AppointmentCatalog::contactLabel() }}</th>
                    @endif
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

                        $rowTrail = NavigationTrail::appendOrCollapse(
                            $containerTrail,
                            NavigationTrail::makeNode(
                                'appointments.show',
                                $appointment->id,
                                $appointment->title ?: 'Turno #' . $appointment->id,
                                route('appointments.show', ['appointment' => $appointment]),
                            ),
                        );

                        if (empty($rowTrail)) {
                            $rowTrail = NavigationTrail::base([
                                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                                NavigationTrail::makeNode(
                                    'appointments.calendar',
                                    null,
                                    'Turnos',
                                    route('appointments.calendar'),
                                ),
                                NavigationTrail::makeNode(
                                    'appointments.show',
                                    $appointment->id,
                                    $appointment->title ?: 'Turno #' . $appointment->id,
                                    route('appointments.show', ['appointment' => $appointment]),
                                ),
                            ]);
                        }

                        $rowTrailQuery = NavigationTrail::toQuery($rowTrail);

                        $orderAction = OrderLinked::forAppointment($appointment, $rowTrailQuery, false);
                        $partyLinked = PartyLinked::forParty(
                            $appointment->party,
                            $rowTrailQuery,
                            AppointmentCatalog::contactLabel(),
                        );
                        $assetAction = AssetLinked::forAsset(
                            $appointment->asset,
                            $rowTrailQuery,
                            AppointmentCatalog::assetLabel(),
                        );
                    @endphp

                    <tr>
                        <td>
                            <a href="{{ route('appointments.show', ['appointment' => $appointment] + $rowTrailQuery) }}">
                                {{ $rowTitle }}
                            </a>
                            <div class="text-muted">#{{ $appointment->id }}</div>
                        </td>

                        @if ($renderPartyColumn)
                            <td>
                                @include('parties.components.linked-party', [
                                    'linked' => $partyLinked,
                                    'variant' => 'inline',
                                ])
                            </td>
                        @endif

                        @if ($supportsAssetsModule)
                            <td>
                                @include('assets.components.linked-asset', [
                                    'action' => $assetAction,
                                    'variant' => 'inline',
                                ])
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
                                @include('orders.components.linked-order', [
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