{{-- FILE: resources/views/assets/partials/table.blade.php | V6 --}}

@php
    use App\Support\Auth\TenantModuleAccess;
    use App\Support\Catalogs\AssetCatalog;
    use App\Support\Catalogs\ModuleCatalog;
    use App\Support\Navigation\NavigationTrail;
    use App\Support\Parties\PartyLinked;

    $assets = $assets ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay activos para mostrar.';
    $showParty = $showParty ?? false;
    $trailQuery = $trailQuery ?? [];
    $containerTrail = NavigationTrail::decode($trailQuery['trail'] ?? null);

    $tenant = app('tenant');

    $supportsPartiesModule = TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant);

    $renderPartyColumn = $showParty && $supportsPartiesModule;
@endphp

@if ($assets->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>

                    @if ($renderPartyColumn)
                        <th>Contacto</th>
                    @endif

                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Relación</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assets as $asset)
                    @php
                        $rowTrail = NavigationTrail::appendOrCollapse(
                            $containerTrail,
                            NavigationTrail::makeNode(
                                'assets.show',
                                $asset->id,
                                $asset->name ?: 'Activo #' . $asset->id,
                                route('assets.show', ['asset' => $asset]),
                            ),
                        );

                        if (empty($rowTrail)) {
                            $rowTrail = NavigationTrail::base([
                                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                                NavigationTrail::makeNode('assets.index', null, 'Activos', route('assets.index')),
                                NavigationTrail::makeNode(
                                    'assets.show',
                                    $asset->id,
                                    $asset->name ?: 'Activo #' . $asset->id,
                                    route('assets.show', ['asset' => $asset]),
                                ),
                            ]);
                        }

                        $rowTrailQuery = NavigationTrail::toQuery($rowTrail);

                        $partyLinked = PartyLinked::forParty($asset->party, $rowTrailQuery, 'Contacto');
                    @endphp

                    <tr>
                        <td>{{ $asset->id }}</td>

                        <td>
                            <a href="{{ route('assets.show', ['asset' => $asset] + $rowTrailQuery) }}">
                                {{ $asset->name }}
                            </a>
                        </td>

                        @if ($renderPartyColumn)
                            <td>
                                @include('parties.components.linked-party', [
                                    'linked' => $partyLinked,
                                    'variant' => 'inline',
                                ])
                            </td>
                        @endif

                        <td>{{ $asset->internal_code ?: '—' }}</td>
                        <td>{{ AssetCatalog::kindLabel($asset->kind) }}</td>

                        <td>
                            <span class="status-badge {{ AssetCatalog::badgeClass($asset->status) }}">
                                {{ AssetCatalog::statusLabel($asset->status) }}
                            </span>
                        </td>

                        <td>{{ AssetCatalog::relationshipTypeLabel($asset->relationship_type) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif