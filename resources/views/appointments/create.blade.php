{{-- FILE: resources/views/appointments/create.blade.php | V5 --}}

@extends('layouts.app')

@section('title', 'Nuevo turno')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            route('appointments.calendar', [
                'view' => 'month',
                'month' => now()->format('Y-m'),
            ]),
        );
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Nuevo turno" />

        <x-card>
            <form method="POST" action="{{ route('appointments.store', $trailQuery) }}" class="form">
                @csrf

                @include('appointments._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
