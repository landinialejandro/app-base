{{-- FILE: resources/views/appointments/partials/calendar-toolbar.blade.php | V3 --}}

@php
    use App\Support\Catalogs\AppointmentCatalog;

    $viewMode = $viewMode ?? 'month';

    if ($viewMode === 'week') {
        $queryBase = request()->except('date', 'month', 'page');
        $clearUrl = route('appointments.calendar', [
            'view' => 'week',
            'date' => $currentDate->toDateString(),
        ]);
    } else {
        $queryBase = request()->except('month', 'date', 'page');
        $clearUrl = route('appointments.calendar', [
            'view' => 'month',
            'month' => $currentMonth->format('Y-m'),
        ]);
    }
@endphp

<x-list-filters-card :action="route('appointments.calendar')" :clear-url="$clearUrl" secondary-id="appointments-calendar-extra-filters">
    <x-slot:primary>
        <div class="list-filters-grid">
            <div class="form-group">
                <label class="form-label">Vista</label>

                <div class="calendar-nav">
                    <a href="{{ route('appointments.calendar', array_merge(request()->except('page', 'date', 'month'), ['view' => 'month', 'month' => now()->format('Y-m')])) }}"
                        class="btn {{ $viewMode === 'month' ? 'btn-primary' : 'btn-secondary' }}">
                        Mensual
                    </a>

                    <a href="{{ route('appointments.calendar', array_merge(request()->except('page', 'date', 'month'), ['view' => 'week', 'date' => now()->toDateString()])) }}"
                        class="btn {{ $viewMode === 'week' ? 'btn-primary' : 'btn-secondary' }}">
                        Semanal
                    </a>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ $viewMode === 'week' ? 'Semana' : 'Mes' }}</label>

                <div class="calendar-nav">
                    @if ($viewMode === 'week')
                        <a href="{{ route('appointments.calendar', array_merge($queryBase, ['view' => 'week', 'date' => $previousDate])) }}"
                            class="btn btn-secondary" title="Semana anterior" aria-label="Semana anterior">
                            <x-icons.chevron-left />
                        </a>

                        <div class="calendar-nav-current">
                            {{ $currentWeekStart->format('d/m/Y') }} - {{ $currentWeekEnd->format('d/m/Y') }}
                        </div>

                        <a href="{{ route('appointments.calendar', array_merge($queryBase, ['view' => 'week', 'date' => $nextDate])) }}"
                            class="btn btn-secondary" title="Semana siguiente" aria-label="Semana siguiente">
                            <x-icons.chevron-right />
                        </a>
                    @else
                        <a href="{{ route('appointments.calendar', array_merge($queryBase, ['view' => 'month', 'month' => $previousMonth])) }}"
                            class="btn btn-secondary" title="Mes anterior" aria-label="Mes anterior">
                            <x-icons.chevron-left />
                        </a>

                        <div class="calendar-nav-current">
                            {{ $currentMonth->translatedFormat('F Y') }}
                        </div>

                        <a href="{{ route('appointments.calendar', array_merge($queryBase, ['view' => 'month', 'month' => $nextMonth])) }}"
                            class="btn btn-secondary" title="Mes siguiente" aria-label="Mes siguiente">
                            <x-icons.chevron-right />
                        </a>
                    @endif
                </div>

                <div class="calendar-nav-today">
                    @if ($viewMode === 'week')
                        <a
                            href="{{ route('appointments.calendar', array_merge($queryBase, ['view' => 'week', 'date' => now()->toDateString()])) }}">
                            Ir a hoy
                        </a>
                    @else
                        <a
                            href="{{ route('appointments.calendar', array_merge($queryBase, ['view' => 'month', 'month' => now()->format('Y-m')])) }}">
                            Ir a hoy
                        </a>
                    @endif
                </div>
            </div>

            <div class="form-group">
                <label for="scope" class="form-label">Vista operativa</label>
                <select id="scope" name="scope" class="form-control">
                    <option value="mine" @selected($scope === 'mine')>Mis turnos</option>
                    @if ($canViewAllAppointments ?? false)
                        <option value="all" @selected($scope === 'all')>Todos los turnos</option>
                    @endif
                </select>
            </div>
        </div>
    </x-slot:primary>

    <x-slot:secondary>
        <input type="hidden" name="view" value="{{ $viewMode }}">

        @if ($viewMode === 'week')
            <input type="hidden" name="date" value="{{ $currentDate->toDateString() }}">
        @else
            <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}">
        @endif

        <div class="list-filters-grid">
            <div class="form-group">
                <label for="assigned_user_id" class="form-label">{{ AppointmentCatalog::assignedUserLabel() }}</label>
                <select id="assigned_user_id" name="assigned_user_id" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $selectedAssignedUserId === (string) $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Estado</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Todos</option>
                    @foreach (AppointmentCatalog::statusLabels() as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-slot:secondary>
</x-list-filters-card>
