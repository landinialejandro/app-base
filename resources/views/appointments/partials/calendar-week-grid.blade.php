{{-- FILE: resources/views/appointments/partials/calendar-week-grid.blade.php | V2 --}}

@php
    $weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $supportsAssetsModule = $supportsAssetsModule ?? false;
    $supportsOrdersModule = $supportsOrdersModule ?? false;
@endphp

<div class="appointment-calendar-scroll" data-appointment-calendar-scroll>
    <div class="appointment-calendar appointment-calendar--week">
        <div class="appointment-calendar-head appointment-calendar-head--week">
            @foreach ($days as $index => $day)
                <div class="appointment-calendar-weekday">
                    <div>{{ $weekdays[$index] }}</div>
                    <div class="text-muted">{{ $day['date']->format('d/m') }}</div>
                </div>
            @endforeach
        </div>

        <div class="appointment-calendar-body appointment-calendar-body--week">
            @foreach ($days as $day)
                <div class="appointment-calendar-day-slot"
                    @if ($day['is_today']) data-calendar-today-cell @endif>
                    @include('appointments.partials.calendar-day-cell', [
                        'day' => $day,
                        'mode' => 'week',
                        'maxVisibleAppointments' => 8,
                        'supportsAssetsModule' => $supportsAssetsModule,
                        'supportsOrdersModule' => $supportsOrdersModule,
                    ])
                </div>
            @endforeach
        </div>
    </div>
</div>
