{{-- FILE: resources/views/orders/partials/table.blade.php | V18 --}}

@php
    use App\Support\Catalogs\OrderCatalog;
    use App\Support\Navigation\NavigationTrail;

    $orders = $orders ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay órdenes para mostrar.';
    $showCounterparty = $showCounterparty ?? ($showParty ?? false);
    $showAsset = $showAsset ?? false;
    $trailQuery = $trailQuery ?? [];
    $containerTrail = NavigationTrail::decode($trailQuery['trail'] ?? null);

    $renderCounterpartyColumn = $showCounterparty;
    $renderAssetColumn = $showAsset;
@endphp

@if ($orders->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Estado</th>

                    @if ($renderCounterpartyColumn)
                        <th>Contraparte</th>
                    @endif

                    @if ($renderAssetColumn)
                        <th>Activo</th>
                    @endif

                    <th>Fecha</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    @php
                        $rowTrail = NavigationTrail::appendOrCollapse(
                            $containerTrail,
                            NavigationTrail::makeNode(
                                'orders.show',
                                $order->id,
                                $order->number ?: 'Orden #' . $order->id,
                                route('orders.show', ['order' => $order]),
                            ),
                        );

                        if (empty($rowTrail)) {
                            $rowTrail = NavigationTrail::base([
                                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                                NavigationTrail::makeNode('orders.index', null, 'Órdenes', route('orders.index')),
                                NavigationTrail::makeNode(
                                    'orders.show',
                                    $order->id,
                                    $order->number ?: 'Orden #' . $order->id,
                                    route('orders.show', ['order' => $order]),
                                ),
                            ]);
                        }

                        $rowTrailQuery = NavigationTrail::toQuery($rowTrail);

                        $counterpartyName = $order->displayCounterpartyName();
                        $assetReference = $order->displayAssetReference();
                    @endphp

                    <tr>
                        <td>
                            <a href="{{ route('orders.show', ['order' => $order] + $rowTrailQuery) }}">
                                {{ $order->number ?: 'Sin número' }}
                            </a>
                        </td>

                        <td>{{ OrderCatalog::groupLabel($order->group) }}</td>

                        <td>
                            <span class="status-badge {{ OrderCatalog::badgeClass($order->status) }}">
                                {{ OrderCatalog::statusLabel($order->status) }}
                            </span>
                        </td>

                        @if ($renderCounterpartyColumn)
                            <td>
                                <div>{{ $counterpartyName }}</div>

                                @if ($order->hasManualCounterpartyReference())
                                    <small>Manual</small>
                                @endif
                            </td>
                        @endif

                        @if ($renderAssetColumn)
                            <td>
                                <div>{{ $assetReference }}</div>

                                @if ($order->hasManualAssetReference())
                                    <small>Manual</small>
                                @endif
                            </td>
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

<x-dev-component-version name="orders.partials.table" version="V18" align="right" />