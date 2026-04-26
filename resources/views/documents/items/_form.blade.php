{{-- FILE: resources/views/documents/items/_form.blade.php | V7 --}}

@php
    use App\Support\Catalogs\DocumentItemCatalog;
    use App\Support\Inventory\DocumentItemStatusService;
    use App\Support\LineItems\LineItemViewHelper;

    $itemExists = isset($item) && $item->exists;
    $executedQuantity = $itemExists ? app(DocumentItemStatusService::class)->executedQuantity($item) : 0.0;

    $lineItemState = app(LineItemViewHelper::class)->formState(
        $item,
        DocumentItemCatalog::class,
        $executedQuantity,
    );
@endphp

<x-line-item-form
    :item="$item"
    :products="$products"
    :supports-products-module="true"
    :item-exists="$lineItemState['itemExists']"
    :executed-quantity="$lineItemState['executedQuantity']"
    :line-status-label="$lineItemState['lineStatusLabel']"
    :line-status-badge="$lineItemState['lineStatusBadge']"
/>