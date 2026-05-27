{{-- FILE: resources/views/shops/tabs/commercial.blade.php | V1 --}}

@php
    $commercialConfig = $commercialConfig ?? [];
    $providerTarget = $commercialConfig['provider_target'] ?? 'simulated';
@endphp

<x-card class="list-card">
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">Configuración comercial</h2>
        <p class="dashboard-section-text">
            Esta lectura muestra la configuración comercial general de la tienda. La configuración prepara reglas de
            operación, pero no crea por sí misma Orders, Payments, Documents, InventoryMovement ni compromiso de stock.
        </p>
    </div>

    <div class="detail-grid">
        <div class="detail-block">
            <span class="detail-block-label">Checkout habilitado</span>
            <div class="detail-block-value">{{ ($commercialConfig['checkout_enabled'] ?? false) ? 'Sí' : 'No' }}</div>
        </div>

        <div class="detail-block">
            <span class="detail-block-label">Compra directa habilitada</span>
            <div class="detail-block-value">{{ ($commercialConfig['direct_purchase_enabled'] ?? false) ? 'Sí' : 'No' }}</div>
        </div>

        <div class="detail-block">
            <span class="detail-block-label">Control de stock</span>
            <div class="detail-block-value">{{ ($commercialConfig['stock_control_enabled'] ?? false) ? 'Sí' : 'No' }}</div>
        </div>

        <div class="detail-block">
            <span class="detail-block-label">Margen sobre stock objetivo</span>
            <div class="detail-block-value">{{ ($commercialConfig['allow_stock_margin'] ?? false) ? 'Sí' : 'No' }}</div>
        </div>

        <div class="detail-block">
            <span class="detail-block-label">Provider objetivo</span>
            <div class="detail-block-value">{{ $commercialConfig['provider_target_label'] ?? 'Entorno simulado' }}</div>
            @if (in_array($providerTarget, ['mercado_pago', 'modo'], true))
                <div class="form-help">
                    Esta selección no habilita una integración real por sí misma.
                </div>
            @endif
        </div>
    </div>
</x-card>
