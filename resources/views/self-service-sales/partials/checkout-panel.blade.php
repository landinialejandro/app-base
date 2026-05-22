{{-- FILE: resources/views/self-service-sales/partials/checkout-panel.blade.php | V3 --}}

<aside class="shop-drawer" data-checkout-panel hidden>
    <div class="shop-drawer__backdrop" data-checkout-close></div>

    <section class="shop-checkout" aria-label="Checkout">
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
                Mercado Pago · entorno simulado
            </div>

            <p data-checkout-message>Conectando con pasarela de pago…</p>

            <div class="shop-cart-line__warning" data-checkout-status hidden></div>

            <dl class="shop-checkout__details">
                <div>
                    <dt>Estado</dt>
                    <dd data-checkout-payment-status>—</dd>
                </div>
                <div>
                    <dt>ID de pago</dt>
                    <dd data-checkout-payment-id>—</dd>
                </div>
                <div>
                    <dt>Referencia</dt>
                    <dd data-checkout-payment-reference>—</dd>
                </div>
                <div>
                    <dt>Importe</dt>
                    <dd data-checkout-payment-amount>—</dd>
                </div>
            </dl>
        </div>
    </section>
</aside>