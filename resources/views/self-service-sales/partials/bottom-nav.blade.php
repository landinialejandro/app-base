{{-- FILE: resources/views/self-service-sales/partials/bottom-nav.blade.php | V2 --}}

<nav class="shop-bottom-nav" aria-label="Acciones de tienda">
    <button type="button" data-checkout-open>
        <span>$</span>
        Pagar
    </button>

    <button type="button" class="shop-bottom-nav__primary" data-not-implemented="escaneo QR">
        <span>QR</span>
        Escanear
    </button>

    <button type="button" data-profile-open>
        <span>Mi</span>
        Perfil
    </button>
</nav>
