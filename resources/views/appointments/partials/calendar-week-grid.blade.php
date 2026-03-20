@php
    $weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
@endphp

<div class="appointment-calendar-scroll" data-appointment-week-scroll>
    <div class="appointment-calendar appointment-calendar--week">
        <div class="appointment-calendar-head">
            @foreach ($days as $index => $day)
                <div class="appointment-calendar-weekday">
                    <div>{{ $weekdays[$index] }}</div>
                    <div class="text-muted">{{ $day['date']->format('d/m') }}</div>
                </div>
            @endforeach
        </div>

        <div class="appointment-calendar-body appointment-calendar-body--week">
            @foreach ($days as $day)
                <div @if ($day['is_today']) data-calendar-today-column @endif>
                    @include('appointments.partials.calendar-day-cell', [
                        'day' => $day,
                        'maxVisibleAppointments' => 8,
                    ])
                </div>
            @endforeach
        </div>
    </div>
</div>
