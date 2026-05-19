// FILE: public/js/self-service-sales-shop.js | V3

(function () {
    const root = document.querySelector('[data-shop-app]');

    if (!root) {
        return;
    }

    const state = {
        cart: {
            items: [],
            total: 0,
            total_label: '$ 0,00',
        },
        currentProduct: null,
        galleryIndex: 0,
    };

    const cartExperienceEnabled = root.dataset.cartExperienceEnabled === 'true';
    const operationPendingMessage = root.dataset.operationPendingMessage || 'Función no implementada todavía: operación comercial pendiente.';
    const cartShowUrl = root.dataset.cartShowUrl;
    const cartAddUrl = root.dataset.cartAddUrl;
    const cartClearUrl = root.dataset.cartClearUrl;
    const checkoutUrl = root.dataset.checkoutUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const productModal = document.querySelector('[data-product-modal]');
    const notImplementedModal = document.querySelector('[data-not-implemented-modal]');
    const profileModal = document.querySelector('[data-profile-modal]');
    const cartDrawer = document.querySelector('[data-cart-drawer]');
    const checkoutPanel = document.querySelector('[data-checkout-panel]');

    const cartItems = document.querySelector('[data-cart-items]');
    const cartTotal = document.querySelector('[data-cart-total]');
    const checkoutTotal = document.querySelector('[data-checkout-total]');
    const filterEmpty = document.querySelector('[data-filter-empty]');

    const parseProduct = (card) => {
        try {
            return JSON.parse(card.dataset.product || '{}');
        } catch (error) {
            return {};
        }
    };

    const show = (element) => {
        if (element) {
            element.hidden = false;
        }
    };

    const hide = (element) => {
        if (element) {
            element.hidden = true;
        }
    };

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers || {}),
            },
            ...options,
        });

        const payload = await response.json().catch(() => ({
            ok: false,
            message: 'No pudimos actualizar el carrito.',
        }));

        if (!response.ok) {
            throw payload;
        }

        return payload;
    };

    const applyCart = (cart) => {
        state.cart = cart || {
            items: [],
            total: 0,
            total_label: '$ 0,00',
        };
        renderCart();
    };

    const handleCartPayload = (payload) => {
        if (payload?.cart) {
            applyCart(payload.cart);
        }

        if (payload && payload.ok === false && payload.message) {
            showNotImplemented(payload.message);
        }

        return payload;
    };

    const handleError = (error) => {
        if (error?.cart) {
            applyCart(error.cart);
        }

        showNotImplemented(error?.message || 'No pudimos actualizar el carrito.');
    };

    const renderTotals = () => {
        const total = state.cart?.total_label || '$ 0,00';

        if (cartTotal) {
            cartTotal.textContent = total;
        }

        if (checkoutTotal) {
            checkoutTotal.textContent = total;
        }
    };

    const renderCart = () => {
        if (!cartItems) {
            return;
        }

        cartItems.innerHTML = '';

        if (!state.cart.items || !state.cart.items.length) {
            const empty = document.createElement('div');
            empty.className = 'shop-empty-state';
            empty.textContent = 'El carrito está vacío.';
            cartItems.appendChild(empty);
            renderTotals();
            return;
        }

        state.cart.items.forEach((line) => {
            const item = document.createElement('div');
            item.className = 'shop-cart-line';
            item.innerHTML = `
                <div class="shop-cart-line__top">
                    <strong></strong>
                    <span></span>
                </div>
                <div class="shop-cart-line__qty">
                    <button type="button" data-cart-dec aria-label="Disminuir">−</button>
                    <span></span>
                    <button type="button" data-cart-inc aria-label="Incrementar">+</button>
                    <button type="button" class="btn btn-secondary" data-cart-remove>Quitar</button>
                </div>
            `;

            item.querySelector('strong').textContent = line.name;
            item.querySelector('.shop-cart-line__top span').textContent = line.subtotal_label;
            item.querySelector('.shop-cart-line__qty span').textContent = `${line.quantity}`;
            item.querySelector('[data-cart-dec]').addEventListener('click', () => changeQuantity(line, -1));
            item.querySelector('[data-cart-inc]').addEventListener('click', () => changeQuantity(line, 1));
            item.querySelector('[data-cart-remove]').addEventListener('click', () => removeFromCart(line));

            cartItems.appendChild(item);
        });

        renderTotals();
    };

    const requireCartExperience = () => {
        if (!cartExperienceEnabled) {
            showNotImplemented(operationPendingMessage);
            return false;
        }

        return true;
    };

    const loadCart = async () => {
        if (!cartExperienceEnabled || !cartShowUrl) {
            renderCart();
            return;
        }

        try {
            const payload = await requestJson(cartShowUrl);
            handleCartPayload(payload);
        } catch (error) {
            handleError(error);
        }
    };

    const addToCart = async (product, quantity = 1) => {
        if (!requireCartExperience()) {
            return;
        }

        if (!product.shopItemId) {
            showNotImplemented('No pudimos identificar el producto publicado en la tienda.');
            return;
        }

        try {
            const payload = await requestJson(cartAddUrl, {
                method: 'POST',
                body: JSON.stringify({
                    shop_item_id: product.shopItemId,
                    quantity: Math.max(1, Number(quantity || 1)),
                }),
            });

            handleCartPayload(payload);
        } catch (error) {
            handleError(error);
        }
    };

    const changeQuantity = async (line, delta) => {
        const nextQuantity = Number(line.quantity || 0) + delta;

        if (nextQuantity <= 0) {
            await removeFromCart(line);
            return;
        }

        try {
            const payload = await requestJson(line.actions.update_url, {
                method: 'PATCH',
                body: JSON.stringify({
                    quantity: nextQuantity,
                }),
            });

            handleCartPayload(payload);
        } catch (error) {
            handleError(error);
        }
    };

    const removeFromCart = async (line) => {
        try {
            const payload = await requestJson(line.actions.delete_url, {
                method: 'DELETE',
                body: JSON.stringify({}),
            });

            handleCartPayload(payload);
        } catch (error) {
            handleError(error);
        }
    };

    const clearCart = async () => {
        if (!requireCartExperience()) {
            return;
        }

        try {
            const payload = await requestJson(cartClearUrl, {
                method: 'DELETE',
                body: JSON.stringify({}),
            });

            handleCartPayload(payload);
        } catch (error) {
            handleError(error);
        }
    };

    const renderGallery = () => {
        const stage = document.querySelector('[data-gallery-stage]');

        if (!stage || !state.currentProduct) {
            return;
        }

        const images = state.currentProduct.images || [];
        const total = Math.max(images.length, 1);
        const current = images[state.galleryIndex] || { label: 'Imagen del producto', url: null };

        if (current.url) {
            stage.innerHTML = '';
            const image = document.createElement('img');
            image.src = current.url;
            image.alt = current.label;
            stage.appendChild(image);
        } else {
            stage.innerHTML = `<div><strong>${current.label}</strong><br><span>Imagen pública pendiente de URL segura.</span><br><span>${state.galleryIndex + 1} de ${total}</span></div>`;
        }
    };

    const openProduct = (product) => {
        state.currentProduct = product;
        state.galleryIndex = 0;

        document.querySelector('[data-detail-name]').textContent = product.name || 'Producto';
        document.querySelector('[data-detail-description]').textContent = product.description || 'Sin descripción ampliada.';
        document.querySelector('[data-detail-price]').textContent = product.priceLabel || 'Precio a confirmar';
        document.querySelector('[data-detail-unit]').textContent = product.unit || '';
        document.querySelector('[data-detail-quantity]').value = 1;

        renderGallery();
        show(productModal);
    };

    const showNotImplemented = (message) => {
        const target = document.querySelector('[data-not-implemented-message]');

        if (target) {
            target.textContent = message;
        }

        show(notImplementedModal);
    };

    const openCheckout = async () => {
        if (!requireCartExperience()) {
            return;
        }

        try {
            const payload = await requestJson(checkoutUrl, {
                method: 'POST',
                body: JSON.stringify({}),
            });

            handleCartPayload(payload);
            show(checkoutPanel);
        } catch (error) {
            handleError(error);
        }
    };

    const filterProducts = (filter) => {
        const cards = Array.from(document.querySelectorAll('.shop-product-card'));
        const terms = {
            all: [],
            fichas: ['ficha', 'fichas'],
            lavado: ['lavado', 'lavar'],
            promos: ['promo', 'promos', 'promoción', 'promocion'],
            servicios: ['servicio', 'servicios'],
        }[filter] || [];
        let visibleCount = 0;

        cards.forEach((card) => {
            const text = (card.dataset.productSearch || '').toLocaleLowerCase('es-AR');
            const visible = !terms.length || terms.some((term) => text.includes(term));
            card.hidden = !visible;

            if (visible) {
                visibleCount += 1;
            }
        });

        if (filterEmpty) {
            filterEmpty.hidden = visibleCount > 0;
        }
    };

    document.querySelectorAll('.shop-product-card').forEach((card) => {
        const product = parseProduct(card);

        card.querySelectorAll('[data-product-view]').forEach((button) => {
            button.addEventListener('click', () => openProduct(product));
        });

        card.querySelector('[data-cart-add]')?.addEventListener('click', async () => {
            await addToCart(product, 1);

            if (cartExperienceEnabled) {
                show(cartDrawer);
            }
        });
    });

    document.querySelector('[data-detail-add]')?.addEventListener('click', async () => {
        if (!state.currentProduct) {
            return;
        }

        await addToCart(state.currentProduct, document.querySelector('[data-detail-quantity]').value);

        if (cartExperienceEnabled) {
            hide(productModal);
            show(cartDrawer);
        }
    });

    document.querySelector('[data-detail-buy]')?.addEventListener('click', async () => {
        if (!state.currentProduct) {
            return;
        }

        await addToCart(state.currentProduct, document.querySelector('[data-detail-quantity]').value);

        if (cartExperienceEnabled) {
            hide(productModal);
            show(cartDrawer);
        }
    });

    document.querySelector('[data-gallery-prev]')?.addEventListener('click', () => {
        const total = Math.max((state.currentProduct?.images || []).length, 1);
        state.galleryIndex = (state.galleryIndex - 1 + total) % total;
        renderGallery();
    });

    document.querySelector('[data-gallery-next]')?.addEventListener('click', () => {
        const total = Math.max((state.currentProduct?.images || []).length, 1);
        state.galleryIndex = (state.galleryIndex + 1) % total;
        renderGallery();
    });

    document.querySelectorAll('[data-product-close]').forEach((button) => button.addEventListener('click', () => hide(productModal)));
    document.querySelectorAll('[data-cart-open]').forEach((button) => button.addEventListener('click', () => show(cartDrawer)));
    document.querySelectorAll('[data-cart-close]').forEach((button) => button.addEventListener('click', () => hide(cartDrawer)));
    document.querySelectorAll('[data-checkout-close]').forEach((button) => button.addEventListener('click', () => hide(checkoutPanel)));
    document.querySelectorAll('[data-not-implemented-close]').forEach((button) => button.addEventListener('click', () => hide(notImplementedModal)));
    document.querySelectorAll('[data-profile-close]').forEach((button) => button.addEventListener('click', () => hide(profileModal)));

    document.querySelector('[data-cart-clear]')?.addEventListener('click', clearCart);
    document.querySelectorAll('[data-checkout-open]').forEach((button) => button.addEventListener('click', openCheckout));

    document.querySelectorAll('[data-not-implemented]').forEach((button) => {
        button.addEventListener('click', () => showNotImplemented(`Función no implementada todavía: ${button.dataset.notImplemented}.`));
    });

    document.querySelector('[data-profile-open]')?.addEventListener('click', () => show(profileModal));

    document.querySelectorAll('[data-shop-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-shop-filter]').forEach((chip) => chip.classList.remove('is-active'));
            button.classList.add('is-active');
            filterProducts(button.dataset.shopFilter || 'all');
        });
    });

    renderCart();
    loadCart();
})();
