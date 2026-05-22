{{-- FILE: resources/views/shops/tabs/items.blade.php | V2 --}}

@php
    use App\Support\Catalogs\ShopCatalog;

    $trailQuery = $trailQuery ?? [];
    $statuses = ShopCatalog::itemFilterLabels();

    $canUpdateShop = auth()->user()?->can('update', $shop) === true;
@endphp

<x-tabs-embedded
    :items="$items"
    :statuses="$statuses"
    tabs-id="shop-items-tabs"
    toolbar-label="Estados de artículos"
    table-view="shops.tabs.items-table"
    :table-data="[
        'shop' => $shop,
        'canUpdateShop' => $canUpdateShop,
        'trailQuery' => $trailQuery,
    ]"
    :add-url="$canUpdateShop ? route('shops.items.create', ['shop' => $shop] + $trailQuery) : null"
    add-label="Agregar artículo"
/>