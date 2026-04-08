{{-- FILE: resources/views/parties/edit.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Editar contacto')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('parties.show', ['party' => $party]));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar contacto" />

        <x-card>
            <form method="POST" action="{{ route('parties.update', ['party' => $party] + $trailQuery) }}" class="form">
                @csrf
                @method('PUT')

                @include('parties._form', [
                    'party' => $party,
                    'allowedKinds' => $allowedKinds,
                ])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
