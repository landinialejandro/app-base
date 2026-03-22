{{-- FILE: resources/views/assets/create.blade.php --}}

@extends('layouts.app')

@section('title', 'Nuevo activo')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('assets.index'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Nuevo activo" />

        <x-card>
            <form action="{{ route('assets.store', $trailQuery) }}" method="POST" class="form">
                @include('assets._form')
            </form>
        </x-card>
    </x-page>
@endsection
