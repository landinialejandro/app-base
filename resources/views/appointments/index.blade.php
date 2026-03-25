{{-- FILE: resources/views/appointments/index.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Turnos')

@section('content')

    @php
        use App\Support\Catalogs\AppointmentCatalog;
    @endphp

    <x-page class="list-page">
        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Turnos']]" />

        <x-page-header title="Turnos">
            <a href="{{ route('appointments.calendar', ['view' => 'month', 'month' => now()->format('Y-m')]) }}"
                class="btn btn-secondary">
                Calendario mensual
            </a>

            <a href="{{ route('appointments.calendar', ['view' => 'week', 'date' => now()->toDateString()]) }}"
                class="btn btn-secondary">
                Calendario semanal
            </a>

            <a href="{{ route('appointments.create') }}" class="btn btn-success">
                Nuevo turno
            </a>
        </x-page-header>

        <x-list-filters-card :action="route('appointments.index')" secondary-id="appointments-extra-filters">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Título, nota o ID">
                    </div>

                    <div class="form-group">
                        <label for="scope" class="form-label">Vista</label>
                        <select id="scope" name="scope" class="form-control">
                            <option value="mine" @selected(($scope ?? request('scope', 'mine')) === 'mine')>Mis turnos</option>
                            <option value="all" @selected(($scope ?? request('scope', 'mine')) === 'all')>Todos los turnos</option>
                        </select>
                    </div>
                </div>
            </x-slot:primary>

            <x-slot:secondary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach (AppointmentCatalog::statusLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kind" class="form-label">Tipo</label>
                        <select id="kind" name="kind" class="form-control">
                            <option value="">Todos</option>
                            @foreach (AppointmentCatalog::kindLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('kind') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="party_id" class="form-label">Contacto</label>
                        <select id="party_id" name="party_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($parties as $party)
                                <option value="{{ $party->id }}" @selected((string) request('party_id') === (string) $party->id)>
                                    {{ $party->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assigned_user_id" class="form-label">Asignado a</label>
                        <select id="assigned_user_id" name="assigned_user_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_user_id') === (string) $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="scheduled_date" class="form-label">Fecha</label>
                        <input type="date" id="scheduled_date" name="scheduled_date" class="form-control"
                            value="{{ request('scheduled_date') }}">
                    </div>
                </div>
            </x-slot:secondary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('appointments.partials.table', [
                'appointments' => $appointments,
                'emptyMessage' => 'No hay turnos registrados para esta empresa.',
            ])

            @if ($appointments->count())
                {{ $appointments->appends(request()->query())->links() }}
            @endif
        </x-card>
    </x-page>
@endsection
