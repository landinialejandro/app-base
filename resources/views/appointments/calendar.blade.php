{{-- FILE: resources/views/appointments/calendar.blade.php | V5 --}}

@extends('layouts.app')

@section('title', 'Calendario de turnos')

@section('content')
    @php
        use App\Support\Catalogs\AppointmentCatalog;
    @endphp

    <x-page class="list-page">
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Turnos'],
            ['label' => 'Calendario'],
        ]" />

        <x-page-header title="Calendario de turnos">
            @can('create', App\Models\Appointment::class)
                <a href="{{ route('appointments.create') }}" class="btn btn-success">
                    Nuevo turno
                </a>
            @endcan
        </x-page-header>

        @include('appointments.partials.calendar-toolbar', [
            'users' => $users,
            'selectedAssignedUserId' => $selectedAssignedUserId,
            'selectedStatus' => $selectedStatus,
            'viewMode' => $viewMode,
            'currentMonth' => $currentMonth ?? null,
            'previousMonth' => $previousMonth ?? null,
            'nextMonth' => $nextMonth ?? null,
            'currentDate' => $currentDate ?? null,
            'currentWeekStart' => $currentWeekStart ?? null,
            'currentWeekEnd' => $currentWeekEnd ?? null,
            'previousDate' => $previousDate ?? null,
            'nextDate' => $nextDate ?? null,
            'canViewAllAppointments' => $canViewAllAppointments ?? false,
        ])

        <x-card class="list-card appointment-calendar-card">
            @if ($viewMode === 'week')
                @include('appointments.partials.calendar-week-grid', [
                    'days' => $days,
                    'currentWeekStart' => $currentWeekStart,
                    'currentWeekEnd' => $currentWeekEnd,
                    'supportsAssetsModule' => $supportsAssetsModule,
                    'supportsOrdersModule' => $supportsOrdersModule,
                ])
            @else
                @include('appointments.partials.calendar-grid', [
                    'weeks' => $weeks,
                    'currentMonth' => $currentMonth,
                    'supportsAssetsModule' => $supportsAssetsModule,
                    'supportsOrdersModule' => $supportsOrdersModule,
                ])
            @endif
        </x-card>
    </x-page>
@endsection
