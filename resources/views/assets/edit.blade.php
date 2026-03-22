{{-- FILE: resources/views/assets/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'Editar activo')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('assets.show', ['asset' => $asset]));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar activo" />

        <x-card>
            <form action="{{ route('assets.update', ['asset' => $asset] + $trailQuery) }}" method="POST" class="form">
                @method('PUT')
                @include('assets._form', ['asset' => $asset])
            </form>
        </x-card>
    </x-page>
@endsection
