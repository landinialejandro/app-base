{{-- FILE: resources/views/inventory/partials/material-flow-modal.blade.php | V1 --}}

@php
    $material = $material ?? [];
    $modalId = $modalId ?? ('inventory-material-flow-' . uniqid());

    $title = $title ?? 'Material formal';
    $action = $action ?? '#';
    $method = $method ?? 'POST';
    $hiddenFields = $hiddenFields ?? [];

    $productName = $material['product_name'] ?? 'Material';
    $position = $material['order_item_id'] ?? '—';
    $quantityDefault = $quantityDefault ?? '0.01';
    $quantityMax = $quantityMax ?? null;

    $summaryItems = [
        [
            'label' => 'Entregado',
            'value' => $material['delivered_display'] ?? '—',
        ],
        [
            'label' => 'Aplicado',
            'value' => $material['applied_display'] ?? '—',
        ],
        [
            'label' => 'Devuelto',
            'value' => $material['returned_display'] ?? '—',
        ],
        [
            'label' => 'Disponible',
            'value' => $material['available_display'] ?? '—',
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
    :quantity-default="$quantityDefault"
    :quantity-max="$quantityMax"
    submit-variant="primary"
    :submit-label="$title"
    :helper-text="$material['warning_label'] ?? null"
    :use-stepper="true"
/>
