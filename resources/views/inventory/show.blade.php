{{-- FILE: resources/views/inventory/show.blade.php | V5 --}}

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

                <x-show-summary-item-detail-block label="Producto maestro">
                    <a href="{{ route('products.show', ['product' => $product] + $trailQuery) }}">
                        Ver ficha del producto
                    </a>
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
                'selectedProductId' => $product->id,
                'returnContext' => 'inventory.show',
                'submitLabel' => 'Registrar movimiento manual',
                'productFieldId' => 'inventory_manual_product_id',
                'kindFieldId' => 'inventory_manual_kind',
                'quantityFieldId' => 'inventory_manual_quantity',
                'notesFieldId' => 'inventory_manual_notes',
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
