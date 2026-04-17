{{-- FILE: resources/views/inventory/index.blade.php | V3 --}}

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
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Productos</div>
                    <div class="summary-inline-value">{{ $productsCount }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Con stock positivo</div>
                    <div class="summary-inline-value">{{ $productsWithStock }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Movimientos registrados</div>
                    <div class="summary-inline-value">{{ $totalMovements }}</div>
                </div>
            </div>

            <div class="form-help mt-3">
                Esta vista concentra el saldo actual por producto. La operación detallada y el historial viven en la ficha
                de inventario de cada artículo.
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
