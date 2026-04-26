{{-- FILE: resources/views/inventory/partials/document-line-return-modal.blade.php | V2 --}}

@php
    $row = $row ?? [];
    $modalId = $modalId ?? ('inventory-document-line-return-' . uniqid());

    $title = $title ?? 'Revertir';
    $action = $action ?? '#';
    $method = $method ?? 'POST';
    $hiddenFields = $hiddenFields ?? [];

    $position = $row['position'] ?? '—';
    $productName = $row['product_name'] ?? 'Producto';

    $pendingQuantity = (float) ($row['pending_quantity'] ?? 0);
    $executedQuantity = (float) ($row['executed_quantity'] ?? 0);
    $maxReturnQuantity = (float) ($row['max_return_quantity'] ?? $executedQuantity);
    $currentStock = array_key_exists('current_stock', $row) ? (float) $row['current_stock'] : null;

    $defaultQuantity = number_format($maxReturnQuantity, 2, '.', '');

    $summaryItems = [
        [
            'label' => 'Pendiente',
            'value' => number_format($pendingQuantity, 2, ',', '.'),
        ],
        [
            'label' => 'Ejecutado neto',
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
    quantity-label="Cantidad a revertir"
    :quantity-default="$defaultQuantity"
    :quantity-max="$defaultQuantity"
    submit-variant="danger"
    :helper-text="'Podés revertir hasta ' . number_format($maxReturnQuantity, 2, ',', '.') . ', que corresponde a lo ejecutado neto actual de la línea.'"
/>