{{-- FILE: resources/views/documents/items/partials/table.blade.php | V3 --}}

@php
    use App\Support\Catalogs\ProductCatalog;

    $document = $document ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en este documento.';
    $trailQuery = $trailQuery ?? [];
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
                    <th>Precio unitario</th>
                    <th>Total línea</th>
                    <th class="compact-actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->position }}</td>
                        <td>{{ ProductCatalog::kindLabel($item->kind) }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
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
                                </div>
                            @else
                                —
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
