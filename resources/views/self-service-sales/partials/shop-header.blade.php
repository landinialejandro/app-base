{{-- FILE: resources/views/self-service-sales/partials/shop-header.blade.php | V2 --}}

<header class="shop-hero">
    <div class="shop-hero__identity">
        <div class="shop-hero__mark" aria-hidden="true">{{ mb_substr($tenant->name, 0, 1) }}</div>

        <div>
            <h1>{{ $activeShop?->name ?: $tenant->name }}</h1>
            <p>{{ $tenant->name }}</p>
        </div>
    </div>

    <div class="shop-hero__actions">
        <button type="button" class="shop-header-action" data-cart-open>Carrito</button>
        <button type="button" class="shop-header-action" data-profile-open>Perfil</button>
    </div>
</header>
