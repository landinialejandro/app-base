{{-- FILE: resources/views/self-service-sales/partials/not-implemented-modal.blade.php | V2 --}}

<div class="shop-modal" data-not-implemented-modal hidden>
    <div class="shop-modal__backdrop" data-not-implemented-close></div>

    <section class="shop-simple-modal" role="dialog" aria-modal="true" aria-labelledby="shop-not-implemented-title">
        <button type="button" class="shop-icon-button shop-simple-modal__close" data-not-implemented-close aria-label="Cerrar">×</button>
        <h2 id="shop-not-implemented-title">Función pendiente</h2>
        <p data-not-implemented-message>Función no implementada todavía.</p>
    </section>
</div>

<div class="shop-modal" data-profile-modal hidden>
    <div class="shop-modal__backdrop" data-profile-close></div>

    <section class="shop-simple-modal" role="dialog" aria-modal="true" aria-labelledby="shop-profile-title">
        <button type="button" class="shop-icon-button shop-simple-modal__close" data-profile-close aria-label="Cerrar">×</button>
        <h2 id="shop-profile-title">Perfil externo</h2>

        @if($externalCustomer)
            <p>{{ $externalCustomer['display_name'] }} · {{ $externalCustomer['identity_label'] }}</p>
            <p>Estado: {{ $externalCustomer['operation_enabled'] ? 'operación habilitada' : 'operación pendiente' }}.</p>
        @else
            <p>Estás navegando como visitante.</p>
            <p>Podés registrarte o ingresar desde los accesos de la tienda.</p>
        @endif
    </section>
</div>
