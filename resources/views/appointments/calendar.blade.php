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
            'currentMonth' => $currentMonth,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
        ])

        <x-card class="list-card appointment-calendar-card">
            @include('appointments.partials.calendar-grid', [
                'weeks' => $weeks,
                'currentMonth' => $currentMonth,
            ])
        </x-card>
    </x-page>
@endsection
