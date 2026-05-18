{{-- FILE: resources/views/self-service-sales/partials/product-masonry.blade.php | V2 --}}

<section class="shop-catalog">
    <div class="shop-category-strip" aria-label="Categorías">
        <button type="button" class="shop-category-chip is-active" data-shop-filter="all">Todos</button>
        <button type="button" class="shop-category-chip" data-shop-filter="fichas">Fichas</button>
        <button type="button" class="shop-category-chip" data-shop-filter="lavado">Lavado</button>
        <button type="button" class="shop-category-chip" data-shop-filter="promos">Promos</button>
        <button type="button" class="shop-category-chip" data-shop-filter="servicios">Servicios</button>
    </div>

    <div class="shop-section-heading">
        <div>
            <h2>Explorar productos</h2>
        </div>
    </div>

    @if($shopItems->isEmpty())
        <div class="shop-empty-state">
            @if($shopCatalogStatus === 'without_active_shop')
                Esta tienda todavía no tiene una configuración activa publicada.
            @else
                Esta tienda activa todavía no tiene artículos publicados.
            @endif
        </div>
    @else
        <div class="shop-empty-state" data-filter-empty hidden>
            No encontramos productos para esta categoría.
        </div>

        <div class="shop-masonry">
            @foreach($shopItems as $shopItem)
                @include('self-service-sales.partials.product-card', ['shopItem' => $shopItem])
            @endforeach
        </div>
    @endif
</section>
