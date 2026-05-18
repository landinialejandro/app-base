{{-- FILE: resources/views/self-service-sales/partials/cart-drawer.blade.php | V2 --}}

<aside class="shop-drawer" data-cart-drawer hidden>
    <div class="shop-drawer__backdrop" data-cart-close></div>

    <section class="shop-cart" aria-label="Carrito">
        <header class="shop-cart__header">
            <div>
                <span>Resumen</span>
                <h2>Carrito</h2>
            </div>
            <button type="button" class="shop-icon-button" data-cart-close aria-label="Cerrar">×</button>
        </header>

        <div class="shop-cart__items" data-cart-items></div>

        <footer class="shop-cart__footer">
            <div class="shop-cart__total">
                <span>Total</span>
                <strong data-cart-total>$ 0,00</strong>
            </div>

            <div class="shop-cart__actions">
                <button type="button" class="btn btn-secondary" data-cart-clear>Vaciar</button>
                <button type="button" class="btn btn-secondary" data-cart-close>Continuar</button>
                <button type="button" class="btn btn-primary" data-checkout-open>Pagar</button>
            </div>
        </footer>
    </section>
</aside>
