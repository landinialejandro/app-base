{{-- FILE: resources/views/inventory/index.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
    @endphp

    <x-page class="list-page">
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Inventario" />

        <x-card class="list-card">
            @include('inventory.partials.balance-table', [
                'rows' => $rows,
                'trailQuery' => $trailQuery,
            ])
        </x-card>
    </x-page>
@endsection
