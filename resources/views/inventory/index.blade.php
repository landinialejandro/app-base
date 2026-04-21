{{-- FILE: resources/views/inventory/index.blade.php | V5 --}}

@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $rows = ($rows ?? collect())->values();
        $headerActions = ($headerActions ?? collect())->values();
    @endphp

    <x-page class="list-page">
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Inventario">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach
        </x-page-header>

        <x-list-filters-card :action="route('inventory.index')" secondary-id="inventory-extra-filters">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre, SKU o ID">
                    </div>
                </div>
            </x-slot:primary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('inventory.partials.balance-table', [
                'rows' => $rows,
                'trailQuery' => $trailQuery,
            ])
        </x-card>
    </x-page>
@endsection
