{{-- FILE: resources/views/shops/tabs/opening-status.blade.php | V1 --}}

@php
    $openingStatus = $openingStatus ?? [];

    $shopActive = (bool) ($openingStatus['shop_active'] ?? false);
    $configuredItemsCount = (int) ($openingStatus['configured_items_count'] ?? 0);
    $publicVisibleItemsCount = (int) ($openingStatus['public_visible_items_count'] ?? 0);
    $cartEnabledItemsCount = (int) ($openingStatus['cart_enabled_items_count'] ?? 0);
    $checkoutEnabled = (bool) ($openingStatus['checkout_enabled'] ?? false);
    $providerTarget = $openingStatus['provider_target'] ?? 'simulated';
    $providerTargetLabel = $openingStatus['provider_target_label'] ?? 'Entorno simulado';
    $operationalCustomersCount = (int) ($openingStatus['operational_customers_count'] ?? 0);
@endphp

<x-card class="list-card">
    <div class="content-section-header">
        <h2 class="content-section-title">Estado de apertura</h2>
        <p class="content-section-text">
            Este diagnóstico ayuda a revisar si la tienda está preparada para operar en modo simulado/controlado. No
            reemplaza autorización backend ni validaciones de checkout.
        </p>
    </div>

    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Dato</th>
                    <th>Estado / valor</th>
                    <th>Lectura</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tienda activa</td>
                    <td>{{ $shopActive ? 'Sí' : 'No' }}</td>
                    <td>
                        {{ $shopActive ? 'La tienda interna está activa.' : 'La tienda pública no debería operar mientras la tienda interna no esté activa.' }}
                    </td>
                </tr>

                <tr>
                    <td>Artículos configurados</td>
                    <td>{{ $configuredItemsCount }}</td>
                    <td>Artículos publicados o preparados dentro del perfil interno de tienda.</td>
                </tr>

                <tr>
                    <td>Artículos visibles públicamente</td>
                    <td>{{ $publicVisibleItemsCount }}</td>
                    <td>
                        {{ $publicVisibleItemsCount > 0 ? 'Hay artículos preparados para exhibición pública.' : 'No hay artículos visibles para la tienda pública.' }}
                    </td>
                </tr>

                <tr>
                    <td>Artículos con carrito habilitado</td>
                    <td>{{ $cartEnabledItemsCount }}</td>
                    <td>
                        {{ $cartEnabledItemsCount > 0 ? 'Hay artículos cuya política efectiva permite carrito.' : 'No hay artículos habilitados para carrito.' }}
                    </td>
                </tr>

                <tr>
                    <td>Checkout habilitado</td>
                    <td>{{ $checkoutEnabled ? 'Sí' : 'No' }}</td>
                    <td>
                        {{ $checkoutEnabled ? 'El checkout puede avanzar a sus validaciones backend.' : 'El checkout será bloqueado antes del provider.' }}
                    </td>
                </tr>

                <tr>
                    <td>Provider objetivo</td>
                    <td>{{ $providerTargetLabel }}</td>
                    <td>
                        @if (in_array($providerTarget, ['mercado_pago', 'modo'], true))
                            Provider objetivo configurado; provider real no implementado por esta lectura.
                        @else
                            Operación orientada a entorno simulado/controlado.
                        @endif
                    </td>
                </tr>

                <tr>
                    <td>Customers externos operativos del tenant</td>
                    <td>{{ $operationalCustomersCount }}</td>
                    <td>
                        Dato tenant-scoped para Shopping Autoservicio; no representa vínculo directo Shop-Customer.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</x-card>
