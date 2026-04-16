{{-- FILE: resources/views/inventory/show.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Ficha de inventario')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $inventoryMovements = ($inventoryMovements ?? collect())->values();
        $currentStock = isset($currentStock) ? (float) $currentStock : 0;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('inventory.index'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Ficha de inventario">
            <a href="{{ $backUrl }}" class="btn btn-secondary" title="Volver" aria-label="Volver">
                <x-icons.chevron-left />
            </a>
        </x-page-header>

        <x-show-summary details-id="inventory-more-detail">
            <x-show-summary-item label="Producto">
                {{ $product->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Stock actual">
                {{ number_format($currentStock, 2, ',', '.') }}
            </x-show-summary-item>

            <x-show-summary-item label="Unidad">
                {{ $product->unit_label ?: '—' }}
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item-detail-block label="SKU">
                    {{ $product->sku ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Movimientos">
                    {{ $inventoryMovements->count() }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Descripción" full>
                    {{ $product->description ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <x-card class="list-card">
            @include('inventory.partials.movement-form', [
                'action' => route('inventory.movements.store', $trailQuery),
                'products' => collect([$product]),
                'fixedKind' => 'ingresar',
                'selectedProductId' => $product->id,
                'returnContext' => 'inventory.show',
                'submitLabel' => 'Registrar ingreso',
                'productFieldId' => 'inventory_ingresar_product_id',
                'kindFieldId' => 'inventory_ingresar_kind',
                'quantityFieldId' => 'inventory_ingresar_quantity',
                'notesFieldId' => 'inventory_ingresar_notes',
            ])
        </x-card>

        <x-card class="list-card">
            @include('inventory.partials.movements-table', [
                'movements' => $inventoryMovements,
                'emptyMessage' => 'No hay movimientos de stock registrados para este producto.',
                'trailQuery' => $trailQuery,
            ])
        </x-card>
    </x-page>
@endsection
