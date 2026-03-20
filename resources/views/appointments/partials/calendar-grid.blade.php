@php
    $weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
@endphp

<div class="appointment-calendar">
    <div class="appointment-calendar-head">
        @foreach ($weekdays as $weekday)
            <div class="appointment-calendar-weekday">
                {{ $weekday }}
            </div>
        @endforeach
    </div>

    <div class="appointment-calendar-body">
        @foreach ($weeks as $week)
            @foreach ($week as $day)
                @include('appointments.partials.calendar-day-cell', ['day' => $day])
            @endforeach
        @endforeach
    </div>
</div>
