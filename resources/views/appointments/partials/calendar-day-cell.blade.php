@php
    use App\Support\Catalogs\AppointmentCatalog;

    $appointments = $day['appointments'];
    $visibleAppointments = $appointments->take(4);
    $remainingCount = max($appointments->count() - $visibleAppointments->count(), 0);
    $isPastDay = $day['date']
        ->copy()
        ->startOfDay()
        ->lt(now()->startOfDay());
@endphp

<div
    class="appointment-calendar-day
        {{ $day['is_current_month'] ? 'is-current-month' : 'is-outside-month' }}
        {{ $day['is_today'] ? 'is-today' : '' }}
        {{ $isPastDay ? 'is-past-day' : '' }}">
    <div class="appointment-calendar-day-header">
        <div class="appointment-calendar-day-number">
            {{ $day['date']->day }}
        </div>

        <div class="appointment-calendar-day-actions">
            @unless ($isPastDay)
                <a href="{{ route('appointments.create', ['scheduled_date' => $day['date_key']]) }}"
                    class="appointment-calendar-add" title="Crear turno para {{ $day['date']->format('d/m/Y') }}"
                    aria-label="Crear turno para {{ $day['date']->format('d/m/Y') }}">
                    <x-icons.plus />
                </a>
            @endunless
        </div>
    </div>

    <div class="appointment-calendar-day-summary">
        @if ($appointments->count())
            {{ $appointments->count() }} {{ $appointments->count() === 1 ? 'turno' : 'turnos' }}
        @else
            Sin turnos
        @endif
    </div>

    <div class="appointment-calendar-day-list">
        @forelse ($visibleAppointments as $appointment)
            @php
                $rowTitle = AppointmentCatalog::rowTitleFor($appointment->kind, $appointment->work_mode);
                $timeLabel = $appointment->is_all_day
                    ? 'Día completo'
                    : ($appointment->starts_at && $appointment->ends_at
                        ? $appointment->starts_at->format('H:i') . ' - ' . $appointment->ends_at->format('H:i')
                        : 'Sin horario');
            @endphp

            <a href="{{ route('appointments.show', $appointment) }}"
                class="appointment-calendar-item status-accent-{{ $appointment->status }}">
                <div class="appointment-calendar-item-time">{{ $timeLabel }}</div>

                <div class="appointment-calendar-item-title">
                    {{ $appointment->title ?: $rowTitle }}
                </div>

                <div class="appointment-calendar-item-meta">
                    @if ($appointment->party)
                        <span>{{ $appointment->party->name }}</span>
                    @elseif ($appointment->assignedUser)
                        <span>{{ $appointment->assignedUser->name }}</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="appointment-calendar-empty">
                —
            </div>
        @endforelse

        @if ($remainingCount > 0)
            <div class="appointment-calendar-more">
                +{{ $remainingCount }} más
            </div>
        @endif
    </div>
</div>
