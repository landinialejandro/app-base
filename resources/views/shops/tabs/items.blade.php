{{-- FILE: resources/views/shops/tabs/items.blade.php | V2 --}}

@php
    use App\Models\ShopItem;

    $statuses = [
        ShopItem::STATUS_PUBLISHED => 'Publicados',
        ShopItem::STATUS_DRAFT => 'Borradores',
        ShopItem::STATUS_HIDDEN => 'Ocultos',
    ];

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
    ]"
    :add-url="$canUpdateShop ? route('shops.items.create', $shop) : null"
    add-label="Agregar artículo"
/>