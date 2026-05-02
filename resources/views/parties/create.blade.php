{{-- FILE: resources/views/parties/create.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Nuevo contacto')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('parties.index'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Nuevo contacto" />

        <x-card>
            <form method="POST" action="{{ route('parties.store', $trailQuery) }}" class="form">
                @csrf

                @include('parties._form', [
                    'party' => null,
                    'allowedKinds' => $allowedKinds,
                    'allowedPartyRoles' => $allowedPartyRoles,
                    'defaultKind' => $defaultKind,
                    'defaultRole' => $defaultRole,
                    'canManageEmployeeContacts' => $canManageEmployeeContacts,
                ])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection