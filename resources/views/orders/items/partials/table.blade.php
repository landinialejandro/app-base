{{-- FILE: resources/views/orders/items/partials/table.blade.php | V12 --}}

@php
    use App\Support\Catalogs\OrderCatalog;
    use App\Support\Catalogs\OrderItemCatalog;

    $order = $order ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en esta orden.';
    $trailQuery = $trailQuery ?? [];

    $orderIsReadonly = $order ? OrderCatalog::isReadonlyStatus($order->status) : false;
@endphp

<x-line-item-table
    :parent="$order"
    :items="$items"
    :empty-message="$emptyMessage"
    :trail-query="$trailQuery"
    :catalog-class="OrderItemCatalog::class"
    parent-param-name="order"
    edit-route="orders.items.edit"
    destroy-route="orders.items.destroy"
    row-action-host="orders.items.row"
    row-action-context-key="order"
    :parent-readonly="$orderIsReadonly"
    modal-namespace="order-items"
/>