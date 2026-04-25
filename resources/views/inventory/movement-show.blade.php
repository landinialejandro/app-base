{{-- FILE: resources/views/inventory/movement-show.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Movimiento de inventario')

@section('content')
    @php
        use App\Support\Inventory\InventoryMovementService;
        use App\Support\Inventory\InventoryOperationCatalog;
        use App\Support\Inventory\InventoryOriginCatalog;
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);

        $kindLabels = [
            InventoryMovementService::KIND_INGRESAR => 'Ingresar',
            InventoryMovementService::KIND_CONSUMIR => 'Consumir',
            InventoryMovementService::KIND_ENTREGAR => 'Entregar',
        ];

        $originTypeLabels = [
            InventoryOriginCatalog::TYPE_MANUAL => 'Manual',
            InventoryOriginCatalog::TYPE_ORDER => 'Orden',
            InventoryOriginCatalog::TYPE_DOCUMENT => 'Documento',
        ];

        $originLineTypeLabels = [
            InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM => 'Línea de orden',
            InventoryOriginCatalog::LINE_TYPE_DOCUMENT_ITEM => 'Línea de documento',
        ];

        $signedQuantity = $movement->signedQuantity();

        $product = $movement->product;
        $creator = $movement->creator;
        $operation = $movement->operation;

        $originLabel = $originTypeLabels[$movement->origin_type] ?? ($movement->origin_type ?: '—');
        $originUrl = null;
        $originDisplay = $originLabel;

        if ($originOrder) {
            $originDisplay = $originOrder->number ?: 'Orden #' . $originOrder->id;
            $originUrl = route('orders.show', ['order' => $originOrder] + $trailQuery);
        } elseif ($originDocument) {
            $originDisplay = $originDocument->number ?: 'Documento #' . $originDocument->id;
            $originUrl = route('documents.show', ['document' => $originDocument] + $trailQuery);
        }

        $originLineLabel = $originLineTypeLabels[$movement->origin_line_type] ?? ($movement->origin_line_type ?: '—');
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Movimiento #{{ $movement->id }}">
            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-show-summary details-id="inventory-movement-detail">
            <x-show-summary-item label="Tipo">
                {{ $kindLabels[$movement->kind] ?? ucfirst($movement->kind) }}
            </x-show-summary-item>

            <x-show-summary-item label="Cantidad">
                {{ number_format((float) $movement->quantity, 2, ',', '.') }}
            </x-show-summary-item>

            <x-show-summary-item label="Impacto stock">
                @if ($signedQuantity > 0)
                    +{{ number_format($signedQuantity, 2, ',', '.') }}
                @elseif ($signedQuantity < 0)
                    {{ number_format($signedQuantity, 2, ',', '.') }}
                @else
                    {{ number_format(0, 2, ',', '.') }}
                @endif
            </x-show-summary-item>

            <x-show-summary-item label="Fecha">
                {{ $movement->created_at?->format('d/m/Y H:i') ?? '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Producto">
                @if ($product)
                    <a href="{{ route('inventory.show', ['product' => $product] + $trailQuery) }}">
                        {{ $product->name ?: 'Producto #' . $product->id }}
                    </a>
                @else
                    —
                @endif
            </x-show-summary-item>

            <x-show-summary-item label="Usuario">
                {{ $creator?->name ?: 'Sistema' }}
            </x-show-summary-item>

            <x-show-summary-item label="Operación">
                @if ($operation)
                    #{{ $operation->id }}
                    <div class="text-muted small">
                        {{ InventoryOperationCatalog::label($operation->operation_type) }}
                    </div>
                @else
                    —
                @endif
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item-detail-block label="Origen">
                    @if ($originUrl)
                        <a href="{{ $originUrl }}">{{ $originDisplay }}</a>
                    @else
                        {{ $originDisplay }}
                    @endif
                </x-show-summary-item-detail-block>

                @if ($movement->origin_line_type)
                    <x-show-summary-item-detail-block label="Línea origen">
                        {{ $originLineLabel }}
                        @if ($movement->origin_line_id)
                            #{{ $movement->origin_line_id }}
                        @endif
                    </x-show-summary-item-detail-block>
                @endif

                @if ($originOrderItem)
                    <x-show-summary-item-detail-block label="Posición de línea">
                        #{{ $originOrderItem->position ?? $originOrderItem->id }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="Descripción de línea" full>
                        {{ $originOrderItem->description ?: '—' }}
                    </x-show-summary-item-detail-block>
                @endif

                @if ($operation)
                    <x-show-summary-item-detail-block label="Tipo de operación">
                        {{ InventoryOperationCatalog::label($operation->operation_type) }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="Notas de operación" full>
                        {{ $operation->notes ?: '—' }}
                    </x-show-summary-item-detail-block>
                @endif

                @if ($product)
                    <x-show-summary-item-detail-block label="SKU">
                        {{ $product->sku ?: '—' }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="Unidad">
                        {{ $product->unit_label ?: '—' }}
                    </x-show-summary-item-detail-block>
                @endif

                <x-show-summary-item-detail-block label="Notas completas" full>
                    {{ $movement->notes ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <x-card>
            <h2 class="card-title">Auditoría técnica</h2>

            <x-show-summary details-id="inventory-movement-audit">
                <x-show-summary-item label="Movimiento ID">
                    #{{ $movement->id }}
                </x-show-summary-item>

                <x-show-summary-item label="Operación ID">
                    {{ $movement->inventory_operation_id ? '#' . $movement->inventory_operation_id : '—' }}
                </x-show-summary-item>

                <x-show-summary-item label="Producto ID">
                    {{ $movement->product_id ?: '—' }}
                </x-show-summary-item>

                <x-show-summary-item label="Creado">
                    {{ $movement->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                </x-show-summary-item>

                <x-show-summary-item label="Actualizado">
                    {{ $movement->updated_at?->format('d/m/Y H:i:s') ?? '—' }}
                </x-show-summary-item>

                <x-slot:details>
                    <x-show-summary-item-detail-block label="inventory_operation_id">
                        {{ $movement->inventory_operation_id ?: '—' }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="origin_type">
                        {{ $movement->origin_type ?: '—' }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="origin_id">
                        {{ $movement->origin_id ?: '—' }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="origin_line_type">
                        {{ $movement->origin_line_type ?: '—' }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="origin_line_id">
                        {{ $movement->origin_line_id ?: '—' }}
                    </x-show-summary-item-detail-block>

                    <x-show-summary-item-detail-block label="created_by">
                        {{ $movement->created_by ?: '—' }}
                    </x-show-summary-item-detail-block>
                </x-slot:details>
            </x-show-summary>
        </x-card>
    </x-page>
@endsection