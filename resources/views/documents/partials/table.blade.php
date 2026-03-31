{{-- FILE: resources/views/documents/partials/table.blade.php | V8 --}}

@php
    use App\Support\Catalogs\DocumentCatalog;
    use App\Support\Navigation\NavigationTrail;

    $documents = $documents ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay documentos para mostrar.';
    $showParty = $showParty ?? true;
    $showAsset = $showAsset ?? true;
    $showOrder = $showOrder ?? true;
    $trailQuery = $trailQuery ?? [];
    $containerTrail = NavigationTrail::decode($trailQuery['trail'] ?? null);
@endphp

@if ($documents->count())
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

                    @if ($showOrder)
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

                        @if ($showParty)
                            <td>
                                @if ($document->party)
                                    <a
                                        href="{{ route('parties.show', ['party' => $document->party] + $rowTrailQuery) }}">
                                        {{ $document->party->name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        @endif

                        @if ($showAsset)
                            <td>
                                @if ($document->asset)
                                    <a href="{{ route('assets.show', ['asset' => $document->asset] + $rowTrailQuery) }}">
                                        {{ $document->asset->name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        @endif

                        @if ($showOrder)
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
