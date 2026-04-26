{{-- FILE: resources/views/inventory/partials/line-execute-modal.blade.php | V2 --}}

@php
    $row = $row ?? [];
    $modalId = $modalId ?? ('inventory-line-execute-' . uniqid());

    $title = $title ?? ($row['execute_title'] ?? 'Operar línea');
    $action = $action ?? '#';
    $method = $method ?? 'POST';
    $hiddenFields = $hiddenFields ?? [];

    $position = $row['position'] ?? '—';
    $productName = $row['product_name'] ?? 'Producto';

    $pendingQuantity = (float) ($row['pending_quantity'] ?? $row['quantity'] ?? 0);
    $executedQuantity = (float) ($row['executed_quantity'] ?? 0);
    $currentStock = array_key_exists('current_stock', $row) ? (float) $row['current_stock'] : null;

    $defaultQuantity = number_format($pendingQuantity, 2, '.', '');

    $summaryItems = [
        [
            'label' => 'Pendiente',
            'value' => number_format($pendingQuantity, 2, ',', '.'),
        ],
        [
            'label' => 'Ejecutado',
            'value' => number_format($executedQuantity, 2, ',', '.'),
        ],
        [
            'label' => 'Stock actual',
            'value' => $currentStock !== null ? number_format($currentStock, 2, ',', '.') : '—',
        ],
    ];
@endphp

<x-line-operation-modal
    :modal-id="$modalId"
    :title="$title"
    :position="$position"
    :action="$action"
    :method="$method"
    :hidden-fields="$hiddenFields"
    :product-name="$productName"
    :summary-items="$summaryItems"
    quantity-label="Cantidad"
    :quantity-default="$defaultQuantity"
    :quantity-max="$defaultQuantity"
    submit-variant="primary"
/>