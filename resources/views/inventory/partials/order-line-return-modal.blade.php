{{-- FILE: resources/views/inventory/partials/order-line-return-modal.blade.php | V6 --}}

@php
    use App\Support\Inventory\InventoryOriginCatalog;

    $order = $order ?? null;
    $row = $row ?? [];
    $trailQuery = $trailQuery ?? [];
    $modalId = $modalId ?? 'inventory-return-line-' . ($row['order_item_id'] ?? uniqid());

    $position = $row['position'] ?? '—';
    $productName = $row['product_name'] ?? 'Producto';

    $executedQuantity = (float) ($row['executed_quantity'] ?? 0);
    $maxReturnQuantity = (float) ($row['max_return_quantity'] ?? $executedQuantity);
    $currentStock = array_key_exists('current_stock', $row) ? (float) $row['current_stock'] : null;

    $productId = $row['product_id'] ?? null;
    $orderItemId = $row['order_item_id'] ?? null;
    $returnKind = $row['return_kind'] ?? null;

    $returnTitle = $row['return_title'] ?? ($row['return_label'] ?? 'Devolver') . ' línea';
    $defaultQuantity = number_format($maxReturnQuantity, 2, '.', '');

    $summaryItems = [
        [
            'label' => 'Ejecutado neto',
            'value' => number_format($executedQuantity, 2, ',', '.'),
        ],
        [
            'label' => 'Máximo a revertir',
            'value' => number_format($maxReturnQuantity, 2, ',', '.'),
        ],
        [
            'label' => 'Stock actual',
            'value' => $currentStock !== null ? number_format($currentStock, 2, ',', '.') : '—',
        ],
    ];

    $hiddenFields = [
        'product_id' => $productId,
        'origin_type' => InventoryOriginCatalog::TYPE_ORDER,
        'origin_id' => $order?->id,
        'origin_line_type' => InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM,
        'origin_line_id' => $orderItemId,
        'kind' => $returnKind,
        'return_context' => 'orders.show',
        'return_tab' => 'inventory.embedded',
    ];
@endphp

<x-line-operation-modal
    :modal-id="$modalId"
    :title="$returnTitle"
    :position="$position"
    :action="route('inventory.order-items.return', ['order' => $order, 'item' => $orderItemId] + $trailQuery)"
    method="POST"
    :hidden-fields="$hiddenFields"
    :product-name="$productName"
    :summary-items="$summaryItems"
    quantity-label="Cantidad"
    :quantity-default="$defaultQuantity"
    :quantity-max="$defaultQuantity"
    submit-variant="danger"
    :helper-text="'Podés revertir hasta ' . number_format($maxReturnQuantity, 2, ',', '.') . ', que corresponde a lo ejecutado neto actual de la línea.'"
    :use-stepper="true"
/>