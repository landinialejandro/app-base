{{-- FILE: resources/views/documents/partials/table.blade.php | V3 --}}

@php
    $documents = $documents ?? collect();
    $showParty = $showParty ?? true;
    $showAsset = $showAsset ?? true;
    $showOrder = $showOrder ?? true;
    $emptyMessage = $emptyMessage ?? 'No hay documentos para mostrar.';
    $trailQuery = $trailQuery ?? [];
@endphp

@if ($documents->isEmpty())
    <p class="text-muted">{{ $emptyMessage }}</p>
@else
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Fecha</th>

                    @if ($showParty)
                        <th>Contacto</th>
                    @endif

                    @if ($showAsset)
                        <th>Activo</th>
                    @endif

                    @if ($showOrder)
                        <th>Orden</th>
                    @endif

                    <th class="text-end">Total</th>
                    <th class="table-actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($documents as $document)
                    <tr>
                        <td>{{ $document->number ?: 'Sin número' }}</td>
                        <td>{{ \App\Support\Catalogs\DocumentCatalog::label($document->kind) }}</td>
                        <td>
                            <span
                                class="status-badge {{ \App\Support\Catalogs\DocumentCatalog::badgeClass($document->status) }}">
                                {{ \App\Support\Catalogs\DocumentCatalog::statusLabel($document->status) }}
                            </span>
                        </td>
                        <td>{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</td>

                        @if ($showParty)
                            <td>{{ $document->party?->name ?: '—' }}</td>
                        @endif

                        @if ($showAsset)
                            <td>{{ $document->asset?->name ?: '—' }}</td>
                        @endif

                        @if ($showOrder)
                            <td>
                                @if ($document->order)
                                    {{ $document->order->number ?: 'Orden #' . $document->order->id }}
                                @else
                                    —
                                @endif
                            </td>
                        @endif

                        <td class="text-end">${{ number_format($document->total, 2, ',', '.') }}</td>

                        <td class="table-actions">
                            <a href="{{ route('documents.show', ['document' => $document] + $trailQuery) }}"
                                class="btn btn-secondary btn-sm">
                                Ver
                            </a>

                            @can('update', $document)
                                <a href="{{ route('documents.edit', ['document' => $document] + $trailQuery) }}"
                                    class="btn btn-secondary btn-sm">
                                    Editar
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
