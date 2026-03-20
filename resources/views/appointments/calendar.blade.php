@extends('layouts.app')

@section('title', 'Calendario de turnos')

@section('content')
    @php
        use App\Support\Catalogs\AppointmentCatalog;
    @endphp

    <x-page class="list-page">
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Turnos', 'url' => route('appointments.index')],
            ['label' => 'Calendario'],
        ]" />

        <x-page-header title="Calendario de turnos">
            <a href="{{ route('appointments.create') }}" class="btn btn-primary">
                Nuevo turno
            </a>

            <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                Ver listado
            </a>
        </x-page-header>

        @include('appointments.partials.calendar-toolbar', [
            'users' => $users,
            'scope' => $scope,
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
        ])

        <x-card class="list-card appointment-calendar-card">
            @if ($viewMode === 'week')
                @include('appointments.partials.calendar-week-grid', [
                    'days' => $days,
                    'currentWeekStart' => $currentWeekStart,
                    'currentWeekEnd' => $currentWeekEnd,
                ])
            @else
                @include('appointments.partials.calendar-grid', [
                    'weeks' => $weeks,
                    'currentMonth' => $currentMonth,
                ])
            @endif
        </x-card>
    </x-page>
@endsection
