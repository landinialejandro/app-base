{{-- FILE: resources/views/shops/tabs/carts.blade.php | V1 --}}

@php
    $selfServiceCarts = $selfServiceCarts ?? collect();
@endphp

<x-card class="list-card">
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">Carritos externos</h2>
        <p class="dashboard-section-text">
            Estos carritos pertenecen al plano externo de Shopping Autoservicio. La tienda muestra esta lectura como
            contexto interno; self_service_sales conserva el ownership del carrito y de su operación externa.
        </p>
    </div>

    @if ($selfServiceCarts->isEmpty())
        <p class="empty-state">Todavía no hay carritos externos vinculados a esta tienda.</p>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Carrito</th>
                        <th>Customer</th>
                        <th>Estado</th>
                        <th>Ítems</th>
                        <th>Total estimado</th>
                        <th>Formalización</th>
                        <th>Actualizado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($selfServiceCarts as $cart)
                        @php
                            $itemsCount = $cart->items_count ?? $cart->items->count();
                            $estimatedTotal = $cart->items->sum(function ($item) {
                                return (int) $item->quantity * (float) $item->unit_price_snapshot;
                            });
                            $customer = $cart->storeCustomer?->party?->name
                                ?: $cart->account?->email
                                ?: '—';
                            $formalization = data_get($cart->meta, 'formalization.order_number') ?: 'Pendiente';
                        @endphp
                        <tr>
                            <td>#{{ $cart->id }}</td>
                            <td>{{ $customer }}</td>
                            <td>{{ $cart->status ?: '—' }}</td>
                            <td>{{ $itemsCount }}</td>
                            <td>$ {{ number_format($estimatedTotal, 2, ',', '.') }}</td>
                            <td>{{ $formalization }}</td>
                            <td>{{ $cart->updated_at?->format('d/m/Y H:i') ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>
