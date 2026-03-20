@php
    use App\Support\Catalogs\AppointmentCatalog;

    $queryBase = request()->except('month', 'page');
@endphp

<x-list-filters-card :action="route('appointments.calendar')" secondary-id="appointments-calendar-extra-filters">
    <x-slot:primary>
        <div class="list-filters-grid">
            <div class="form-group">
                <label class="form-label">Mes</label>

                <div class="calendar-nav">
                    <a href="{{ route('appointments.calendar', array_merge($queryBase, ['month' => $previousMonth])) }}"
                        class="btn btn-secondary" title="Mes anterior" aria-label="Mes anterior">
                        <x-icons.chevron-left />
                    </a>

                    <div class="calendar-nav-current">
                        {{ $currentMonth->translatedFormat('F Y') }}
                    </div>

                    <a href="{{ route('appointments.calendar', array_merge($queryBase, ['month' => $nextMonth])) }}"
                        class="btn btn-secondary" title="Mes siguiente" aria-label="Mes siguiente">
                        <x-icons.chevron-right />
                    </a>
                </div>

                <div class="calendar-nav-today">
                    <a
                        href="{{ route('appointments.calendar', array_merge($queryBase, ['month' => now()->format('Y-m')])) }}">
                        Ir a hoy
                    </a>
                </div>
            </div>

            <div class="form-group">
                <label for="scope" class="form-label">Vista</label>
                <select id="scope" name="scope" class="form-control">
                    <option value="mine" @selected($scope === 'mine')>Mis turnos</option>
                    <option value="all" @selected($scope === 'all')>Todos los turnos</option>
                </select>
            </div>
        </div>
    </x-slot:primary>

    <x-slot:secondary>
        <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}">

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
