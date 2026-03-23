{{-- FILE: resources/views/documents/partials/table.blade.php | V4 --}}

@php
    use App\Support\Catalogs\DocumentCatalog;

    $documents = $documents ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay documentos para mostrar.';
    $showParty = $showParty ?? true;
    $showAsset = $showAsset ?? true;
    $showOrder = $showOrder ?? true;
    $trailQuery = $trailQuery ?? [];
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
                    <tr>
                        <td>
                            <a href="{{ route('documents.show', ['document' => $document] + $trailQuery) }}">
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
                                    <a href="{{ route('parties.show', ['party' => $document->party] + $trailQuery) }}">
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
                                    <a href="{{ route('assets.show', ['asset' => $document->asset] + $trailQuery) }}">
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
                                    <a href="{{ route('orders.show', ['order' => $document->order] + $trailQuery) }}">
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
