{{-- FILE: resources/views/orders/partials/table.blade.php | V13 --}}

@php
    use App\Support\Assets\AssetLinked;
    use App\Support\Auth\TenantModuleAccess;
    use App\Support\Catalogs\ModuleCatalog;
    use App\Support\Catalogs\OrderCatalog;
    use App\Support\Navigation\NavigationTrail;
    use App\Support\Parties\PartyLinked;

    $orders = $orders ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay órdenes para mostrar.';
    $showParty = $showParty ?? false;
    $showAsset = $showAsset ?? true;
    $trailQuery = $trailQuery ?? [];
    $containerTrail = NavigationTrail::decode($trailQuery['trail'] ?? null);

    $tenant = app('tenant');
    $user = auth()->user();

    $supportsPartiesModule = TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant);
    $supportsAssetsModule = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);

    $renderPartyColumn = $showParty && $supportsPartiesModule;
    $renderAssetColumn = $showAsset && $supportsAssetsModule;
@endphp

@if ($orders->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Estado</th>

                    @if ($renderPartyColumn)
                        <th>Contacto</th>
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

                        $partyLinked = PartyLinked::forParty($order->party, $rowTrailQuery, 'Contacto');
                        $assetAction = AssetLinked::forAsset($order->asset, $rowTrailQuery, 'Activo');
                    @endphp

                    <tr>
                        <td>
                            <a href="{{ route('orders.show', ['order' => $order] + $rowTrailQuery) }}">
                                {{ $order->number ?: 'Sin número' }}
                            </a>
                        </td>

                        <td>{{ OrderCatalog::label($order->kind) }}</td>

                        <td>
                            <span class="status-badge {{ OrderCatalog::badgeClass($order->status) }}">
                                {{ OrderCatalog::statusLabel($order->status) }}
                            </span>
                        </td>

                        @if ($renderPartyColumn)
                            <td>
                                @include('parties.components.linked-party', [
                                    'linked' => $partyLinked,
                                    'variant' => 'inline',
                                ])
                            </td>
                        @endif

                        @if ($renderAssetColumn)
                            <td>
                                @include('assets.components.linked-asset', [
                                    'action' => $assetAction,
                                    'variant' => 'inline',
                                ])
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
