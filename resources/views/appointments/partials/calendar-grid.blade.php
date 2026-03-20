@php
    $weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
@endphp

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
            <div class="appointment-calendar-weeknumber">
                {{ $week['week_number'] }}
            </div>

            @foreach ($week['days'] as $day)
                @include('appointments.partials.calendar-day-cell', ['day' => $day])
            @endforeach
        @endforeach
    </div>
</div>
