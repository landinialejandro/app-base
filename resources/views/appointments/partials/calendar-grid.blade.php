{{-- FILE: resources/views/appointments/partials/calendar-grid.blade.php | V2 --}}

@php
    $weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    $weekLinkBase = request()->except('view', 'month', 'date', 'page');
@endphp

<div class="appointment-calendar-scroll" data-appointment-calendar-scroll>
    <div class="appointment-calendar">
        <div class="appointment-calendar-head appointment-calendar-head--with-week">
            <div class="appointment-calendar-weeknumber-head">
                Sem.
            </div>

            @foreach ($weekdays as $weekday)
                <div class="appointment-calendar-weekday">
                    {{ $weekday }}
                </div>
            @endforeach
        </div>

        <div class="appointment-calendar-body appointment-calendar-body--with-week">
            @foreach ($weeks as $week)
                <a href="{{ route(
                    'appointments.calendar',
                    array_merge($weekLinkBase, [
                        'view' => 'week',
                        'date' => $week['week_start_date'],
                    ]),
                ) }}"
                    class="appointment-calendar-weeknumber appointment-calendar-weeknumber-link"
                    title="Ver semana {{ $week['week_number'] }}" aria-label="Ver semana {{ $week['week_number'] }}">
                    <span>{{ $week['week_number'] }}</span>
                </a>

                @foreach ($week['days'] as $day)
                    <div class="appointment-calendar-day-slot"
                        @if ($day['is_today']) data-calendar-today-cell @endif>
                        @include('appointments.partials.calendar-day-cell', [
                            'day' => $day,
                            'mode' => 'month',
                            'maxVisibleAppointments' => 4,
                        ])
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
