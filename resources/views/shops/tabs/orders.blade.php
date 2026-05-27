{{-- FILE: resources/views/shops/tabs/orders.blade.php | V1 --}}

@php
    $selfServiceOrders = $selfServiceOrders ?? collect();
    $trailQuery = $trailQuery ?? [];
@endphp

<x-card class="list-card">
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">Ventas / Órdenes</h2>
        <p class="dashboard-section-text">
            Estas órdenes fueron formalizadas desde el checkout externo de Shopping Autoservicio. La tienda muestra esta
            lectura como contexto interno; Orders conserva el ownership de la venta y de su lifecycle.
        </p>
    </div>

    @include('orders.partials.table', [
        'orders' => $selfServiceOrders,
        'showCounterparty' => true,
        'showAsset' => false,
        'trailQuery' => $trailQuery,
        'emptyMessage' => 'Todavía no hay ventas originadas desde Shopping Autoservicio para esta tienda.',
    ])
</x-card>
