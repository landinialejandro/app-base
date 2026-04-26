{{-- FILE: resources/views/orders/items/_form.blade.php | V7 --}}

@php
    use App\Support\Catalogs\OrderItemCatalog;
    use App\Support\Inventory\OrderItemStatusService;
    use App\Support\LineItems\LineItemViewHelper;

    $supportsProductsModule = $supportsProductsModule ?? true;

    $itemExists = isset($item) && $item->exists;
    $executedQuantity = $itemExists ? app(OrderItemStatusService::class)->executedQuantity($item) : 0.0;

    $lineItemState = app(LineItemViewHelper::class)->formState(
        $item,
        OrderItemCatalog::class,
        $executedQuantity,
    );
@endphp

<x-line-item-form
    :item="$item"
    :products="$products"
    :supports-products-module="$supportsProductsModule"
    :item-exists="$lineItemState['itemExists']"
    :executed-quantity="$lineItemState['executedQuantity']"
    :line-status-label="$lineItemState['lineStatusLabel']"
    :line-status-badge="$lineItemState['lineStatusBadge']"
/>