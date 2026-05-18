{{-- FILE: resources/views/self-service-sales/partials/product-detail-modal.blade.php | V2 --}}

<div class="shop-modal" data-product-modal hidden>
    <div class="shop-modal__backdrop" data-product-close></div>

    <section class="shop-product-detail" role="dialog" aria-modal="true" aria-labelledby="shop-product-detail-title">
        <button type="button" class="shop-icon-button shop-product-detail__close" data-product-close aria-label="Cerrar">×</button>

        <div class="shop-gallery">
            <button type="button" class="shop-icon-button" data-gallery-prev aria-label="Foto anterior">‹</button>
            <div class="shop-gallery__stage" data-gallery-stage></div>
            <button type="button" class="shop-icon-button" data-gallery-next aria-label="Foto siguiente">›</button>
        </div>

        <div class="shop-product-detail__body">
            <h2 id="shop-product-detail-title" data-detail-name></h2>
            <p data-detail-description></p>

            <div class="shop-product-detail__price">
                <strong data-detail-price></strong>
                <span data-detail-unit></span>
            </div>

            <label class="shop-quantity">
                <span>Cantidad</span>
                <input type="number" min="1" step="1" value="1" data-detail-quantity>
            </label>

            <div class="shop-product-detail__actions">
                <button type="button" class="btn btn-secondary" data-detail-add>Agregar al carrito</button>
                <button type="button" class="btn btn-primary" data-detail-buy>Comprar ahora</button>
            </div>
        </div>
    </section>
</div>
