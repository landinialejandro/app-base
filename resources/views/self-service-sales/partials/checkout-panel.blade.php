{{-- FILE: resources/views/self-service-sales/partials/checkout-panel.blade.php | V2 --}}

<aside class="shop-drawer" data-checkout-panel hidden>
    <div class="shop-drawer__backdrop" data-checkout-close></div>

    <section class="shop-checkout" aria-label="Checkout simulado">
        <header class="shop-cart__header">
            <div>
                <span>Pago</span>
                <h2>Checkout</h2>
            </div>
            <button type="button" class="shop-icon-button" data-checkout-close aria-label="Cerrar">×</button>
        </header>

        <div class="shop-checkout__body">
            <div class="shop-cart__total">
                <span>Total del carrito</span>
                <strong data-checkout-total>$ 0,00</strong>
            </div>

            <div class="shop-payment-placeholder">
                Mercado Pago
            </div>

            <p>Función no implementada todavía: pago final.</p>
        </div>
    </section>
</aside>
