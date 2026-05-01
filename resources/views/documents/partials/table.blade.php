{{-- FILE: resources/views/documents/partials/table.blade.php | V11 --}}

@php
    use App\Support\Assets\AssetLinked;
    use App\Support\Auth\TenantModuleAccess;
    use App\Support\Catalogs\DocumentCatalog;
    use App\Support\Catalogs\ModuleCatalog;
    use App\Support\Navigation\NavigationTrail;
    use App\Support\Parties\PartyLinked;

    $documents = $documents ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay documentos para mostrar.';
    $showParty = $showParty ?? true;
    $showAsset = $showAsset ?? true;
    $showOrder = $showOrder ?? true;
    $trailQuery = $trailQuery ?? [];
    $containerTrail = NavigationTrail::decode($trailQuery['trail'] ?? null);

    $tenant = app('tenant');

    $supportsPartiesModule = TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant);
    $supportsAssetsModule = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);
    $supportsOrdersModule = TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant);

    $renderPartyColumn = $showParty && $supportsPartiesModule;
    $renderAssetColumn = $showAsset && $supportsAssetsModule;
    $renderOrderColumn = $showOrder && $supportsOrdersModule;
@endphp

@if ($documents->count())
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

                    @if ($renderOrderColumn)
                        <th>Orden</th>
                    @endif

                    <th>Fecha</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($documents as $document)
                    @php
                        $rowTrail = NavigationTrail::appendOrCollapse(
                            $containerTrail,
                            NavigationTrail::makeNode(
                                'documents.show',
                                $document->id,
                                $document->number ?: 'Documento #' . $document->id,
                                route('documents.show', ['document' => $document]),
                            ),
                        );

                        if (empty($rowTrail)) {
                            $rowTrail = NavigationTrail::base([
                                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                                NavigationTrail::makeNode(
                                    'documents.index',
                                    null,
                                    'Documentos',
                                    route('documents.index'),
                                ),
                                NavigationTrail::makeNode(
                                    'documents.show',
                                    $document->id,
                                    $document->number ?: 'Documento #' . $document->id,
                                    route('documents.show', ['document' => $document]),
                                ),
                            ]);
                        }

                        $rowTrailQuery = NavigationTrail::toQuery($rowTrail);

                        $partyLinked = PartyLinked::forParty($document->party, $rowTrailQuery, 'Contacto');
                        $assetAction = AssetLinked::forAsset($document->asset, $rowTrailQuery, 'Activo');
                    @endphp

                    <tr>
                        <td>
                            <a href="{{ route('documents.show', ['document' => $document] + $rowTrailQuery) }}">
                                {{ $document->number ?: 'Sin número' }}
                            </a>
                        </td>

                        <td>{{ DocumentCatalog::label($document->kind) }}</td>

                        <td>
                            <span class="status-badge {{ DocumentCatalog::badgeClass($document->status) }}">
                                {{ DocumentCatalog::statusLabel($document->status) }}
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

                        @if ($renderOrderColumn)
                            <td>
                                @if ($document->order)
                                    <a href="{{ route('orders.show', ['order' => $document->order] + $rowTrailQuery) }}">
                                        {{ $document->order->number ?: 'Ver orden' }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        @endif

                        <td>{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</td>
                        <td>${{ number_format($document->total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif