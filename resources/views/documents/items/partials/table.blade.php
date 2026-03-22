{{-- FILE: resources/views/documents/items/partials/table.blade.php | V3 --}}

@php
    $document = $document ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems para mostrar.';
    $trailQuery = $trailQuery ?? [];
@endphp

@if ($items->isEmpty())
    <p class="text-muted">{{ $emptyMessage }}</p>
@else
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Precio unitario</th>
                    <th class="text-end">Total línea</th>
                    <th class="table-actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->position }}</td>
                        <td>{{ $item->kind }}</td>
                        <td>
                            <div>{{ $item->description }}</div>
                            @if ($item->product)
                                <div class="text-muted">{{ $item->product->name }}</div>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                        <td class="text-end">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                        <td class="text-end">${{ number_format($item->line_total, 2, ',', '.') }}</td>
                        <td class="table-actions">
                            @can('update', $document)
                                <a href="{{ route('documents.items.edit', ['document' => $document, 'item' => $item] + $trailQuery) }}"
                                    class="btn btn-secondary btn-sm">
                                    Editar
                                </a>
                            @endcan

                            @can('update', $document)
                                <form method="POST"
                                    action="{{ route('documents.items.destroy', ['document' => $document, 'item' => $item] + $trailQuery) }}"
                                    class="inline-form" data-action="app-confirm-submit"
                                    data-confirm-message="¿Deseas eliminar este ítem?">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Eliminar
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
