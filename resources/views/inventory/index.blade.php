{{-- FILE: resources/views/inventory/index.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $rows = ($rows ?? collect())->values();

        $productsCount = $rows->count();
        $totalMovements = $rows->sum('movement_count');
        $productsWithStock = $rows->filter(fn($row) => (float) ($row['stock'] ?? 0) > 0)->count();
    @endphp

    <x-page class="list-page">
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Inventario" />

        <x-card>
            <div class="detail-grid">
                <div class="detail-block">
                    <div class="detail-label">Productos</div>
                    <div class="detail-value">{{ $productsCount }}</div>
                </div>

                <div class="detail-block">
                    <div class="detail-label">Con stock positivo</div>
                    <div class="detail-value">{{ $productsWithStock }}</div>
                </div>

                <div class="detail-block">
                    <div class="detail-label">Movimientos registrados</div>
                    <div class="detail-value">{{ $totalMovements }}</div>
                </div>
            </div>
        </x-card>

        <x-card class="list-card">
            @include('inventory.partials.balance-table', [
                'rows' => $rows,
                'trailQuery' => $trailQuery,
            ])
        </x-card>
    </x-page>
@endsection
