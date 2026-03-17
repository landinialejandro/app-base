{{-- FILE: resources/views/assets/partials/table.blade.php --}}

@php
    use App\Support\Catalogs\AssetCatalog;

    $assets = $assets ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay activos para mostrar.';
    $showParty = $showParty ?? false;
@endphp

@if ($assets->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>

                    @if ($showParty)
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
                    <tr>
                        <td>{{ $asset->id }}</td>

                        <td>
                            <a href="{{ route('assets.show', $asset) }}">
                                {{ $asset->name }}
                            </a>
                        </td>

                        @if ($showParty)
                            <td>
                                @if ($asset->party)
                                    <a href="{{ route('parties.show', $asset->party) }}">
                                        {{ $asset->party->name }}
                                    </a>
                                @else
                                    —
                                @endif
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
