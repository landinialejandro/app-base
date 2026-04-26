{{-- FILE: resources/views/documents/items/partials/table.blade.php | V7 --}}

@php
    use App\Support\Catalogs\ProductCatalog;
    use App\Support\Inventory\DocumentItemStatusService;
    use App\Support\Inventory\InventorySurfaceService;
    use App\Support\Modules\ModuleSurfaceRegistry;

    $document = $document ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en este documento.';
    $trailQuery = $trailQuery ?? [];
    $statusService = app(DocumentItemStatusService::class);
@endphp

@if ($items->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Estado</th>
                    <th>Precio unitario</th>
                    <th>Total línea</th>
                    <th class="compact-actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $rowActions = collect();

                        $executedQuantity = $statusService->executedQuantity($item);
                        $pendingQuantity = $statusService->pendingQuantity($item);

                        $lineStatusLabel = 'Pendiente';
                        $lineStatusClass = 'status-badge status-open';

                        if ($executedQuantity > 0 && $pendingQuantity > 0) {
                            $lineStatusLabel = 'Parcial';
                            $lineStatusClass = 'status-badge status-pending';
                        }

                        if ($executedQuantity > 0 && $pendingQuantity <= 0) {
                            $lineStatusLabel = 'Completa';
                            $lineStatusClass = 'status-badge status-completed';
                        }

                        if ($document) {
                            $rowHostPack = app(InventorySurfaceService::class)->hostPack('documents.items.row', $item, [
                                'document' => $document,
                                'trailQuery' => $trailQuery,
                            ]);

                            $rowActions = collect(
                                app(ModuleSurfaceRegistry::class)->linkedFor('documents.items.row', $rowHostPack)
                            )
                                ->where('slot', 'row_actions')
                                ->sortBy(fn ($surface) => $surface['priority'] ?? 999)
                                ->values();
                        }
                    @endphp

                    <tr>
                        <td>{{ $item->position }}</td>
                        <td>{{ ProductCatalog::kindLabel($item->kind) }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                        <td>
                            <span class="{{ $lineStatusClass }}">
                                {{ $lineStatusLabel }}
                            </span>
                        </td>
                        <td>${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                        <td>${{ number_format($item->line_total, 2, ',', '.') }}</td>
                        <td class="compact-actions-cell">
                            @can('update', $document)
                                <div class="compact-actions">
                                    <x-button-tool :href="route(
                                        'documents.items.edit',
                                        ['document' => $document, 'item' => $item] + $trailQuery,
                                    )" title="Editar ítem" label="Editar ítem">
                                        <x-icons.pencil />
                                    </x-button-tool>

                                    <x-button-tool-submit :action="route(
                                        'documents.items.destroy',
                                        ['document' => $document, 'item' => $item] + $trailQuery,
                                    )" method="DELETE" variant="danger"
                                        title="Eliminar ítem" label="Eliminar ítem" message="¿Deseas eliminar este ítem?">
                                        <x-icons.trash />
                                    </x-button-tool-submit>

                                    @foreach ($rowActions as $surface)
                                        @include($surface['view'], $surface['data'] ?? [])
                                    @endforeach
                                </div>
                            @else
                                @if ($rowActions->isNotEmpty())
                                    <div class="compact-actions">
                                        @foreach ($rowActions as $surface)
                                            @include($surface['view'], $surface['data'] ?? [])
                                        @endforeach
                                    </div>
                                @else
                                    —
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif