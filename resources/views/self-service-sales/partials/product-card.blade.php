{{-- FILE: resources/views/self-service-sales/partials/product-card.blade.php | V3 --}}

@php
    $product = $shopItem->product;
    $shopImages = $product?->attachments ?? collect();
    $price = $shopItem->displayPrice();
    $description = $shopItem->displayDescription();
    $displayName = $shopItem->displayName();
    $searchText = \Illuminate\Support\Str::lower($displayName.' '.$description.' '.$product?->unit_label);
    $productPayload = [
        'id' => $shopItem->id,
        'shopItemId' => $shopItem->id,
        'name' => $displayName,
        'description' => $description,
        'shortDescription' => $description ? \Illuminate\Support\Str::limit($description, 120) : null,
        'price' => $price !== null ? (float) $price : null,
        'priceLabel' => $price !== null ? '$ '.number_format((float) $price, 2, ',', '.') : 'Precio a confirmar',
        'unit' => $product?->unit_label,
        'imageCount' => $shopImages->count(),
        'images' => $shopImages->map(fn ($attachment, $index) => [
            'label' => 'Foto '.($index + 1),
            'url' => null,
        ])->values(),
    ];
@endphp

<article
    class="shop-product-card"
    data-product-search="{{ $searchText }}"
    data-product='@json($productPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)'
>
    <button type="button" class="shop-product-card__media" data-product-view aria-label="Ver {{ $displayName }}">
        <span class="shop-product-placeholder" aria-hidden="true">
            <span>{{ mb_substr($displayName, 0, 2) }}</span>
            <small>{{ \Illuminate\Support\Str::limit($displayName, 18) }}</small>
        </span>

        @if($shopImages->count() > 1)
            <span class="shop-photo-count">{{ $shopImages->count() }} fotos</span>
        @endif
    </button>

    <div class="shop-product-card__body">
        <div>
            <h3>{{ $displayName }}</h3>
        </div>

        <div class="shop-product-card__meta">
            <strong>{{ $price !== null ? '$ '.number_format((float) $price, 2, ',', '.') : 'Precio a confirmar' }}</strong>

            @if($product?->unit_label)
                <span>{{ $product->unit_label }}</span>
            @endif
        </div>

        <div class="shop-product-card__actions">
            <button type="button" class="shop-card-link" data-product-view>Ver</button>
            <button type="button" class="btn btn-primary" data-cart-add>Agregar</button>
        </div>
    </div>
</article>
