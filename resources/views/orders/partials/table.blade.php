{{-- FILE: resources/views/orders/partials/table.blade.php | V2 --}}

@php
    use App\Support\Catalogs\OrderCatalog;

    $orders = $orders ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay órdenes para mostrar.';
    $showParty = $showParty ?? false;
    $showAsset = $showAsset ?? true;
@endphp

@if ($orders->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Estado</th>

                    @if ($showParty)
                        <th>Contacto</th>
                    @endif

                    @if ($showAsset)
                        <th>Activo</th>
                    @endif

                    <th>Fecha</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('orders.show', $order) }}">
                                {{ $order->number ?: 'Sin número' }}
                            </a>
                        </td>

                        <td>{{ OrderCatalog::kindLabel($order->kind) }}</td>

                        <td>
                            <span class="status-badge {{ OrderCatalog::badgeClass($order->status) }}">
                                {{ OrderCatalog::statusLabel($order->status) }}
                            </span>
                        </td>

                        @if ($showParty)
                            <td>{{ $order->party?->name ?: '—' }}</td>
                        @endif

                        @if ($showAsset)
                            <td>{{ $order->asset?->name ?: '—' }}</td>
                        @endif

                        <td>{{ $order->ordered_at?->format('d/m/Y') ?: '—' }}</td>
                        <td>${{ number_format($order->total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
