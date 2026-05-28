{{-- FILE: resources/views/self-service-sales/partials/token-consumption-panel.blade.php | V1 --}}

@php
    $tokenPockets = $tokenPockets ?? [];
@endphp

<aside class="shop-drawer" data-token-consumption-panel hidden>
    <div class="shop-drawer__backdrop" data-token-consumption-close></div>

    <section class="shop-checkout" aria-label="Usar fichas">
        <header class="shop-cart__header">
            <div>
                <span>QR</span>
                <h2>Usar fichas</h2>
            </div>
            <button type="button" class="shop-icon-button" data-token-consumption-close aria-label="Cerrar">×</button>
        </header>

        <div class="shop-checkout__body">
            @if($externalCustomer && ! empty($tokenPockets))
                <div class="shop-cart__total">
                    <span>Saldo disponible</span>
                    <strong data-token-consumption-balance>{{ $tokenPockets[0]['summary_label'] }}</strong>
                </div>

                <label>
                    <span>Producto ficha</span>
                    <select data-token-consumption-pocket>
                        @foreach($tokenPockets as $tokenPocket)
                            <option
                                value="{{ $tokenPocket['pocket_id'] }}"
                                data-quantity-available="{{ $tokenPocket['quantity_available'] }}"
                                data-unit-label="{{ $tokenPocket['unit_label'] }}"
                                data-unit-seconds="{{ $tokenPocket['unit_seconds'] }}"
                                data-summary-label="{{ $tokenPocket['summary_label'] }}"
                            >
                                {{ $tokenPocket['name'] }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Cantidad de fichas</span>
                    <input
                        type="number"
                        min="1"
                        step="1"
                        value="1"
                        data-token-consumption-quantity
                    >
                </label>

                <div class="shop-cart__total">
                    <span>Tiempo estimado</span>
                    <strong data-token-consumption-total>{{ $tokenPockets[0]['total_minutes'] ? (($tokenPockets[0]['unit_seconds'] / 60) . ' min') : '—' }}</strong>
                </div>

                <p data-token-consumption-message>
                    Este registro crea un intento de consumo. El saldo no se descuenta en este corte.
                </p>

                <button type="button" class="btn btn-primary" data-token-consumption-submit>
                    Registrar intento
                </button>
            @elseif($externalCustomer)
                <p>No tenés fichas disponibles para usar en este momento.</p>
            @else
                <p>Ingresá como customer externo para usar fichas.</p>
            @endif
        </div>
    </section>
</aside>
