{{-- FILE: resources/views/appointments/edit.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Editar turno')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            route('appointments.show', ['appointment' => $appointment]),
        );
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar turno" />

        <x-card>
            <form method="POST" action="{{ route('appointments.update', ['appointment' => $appointment] + $trailQuery) }}"
                class="form">
                @csrf
                @method('PUT')

                @include('appointments._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
