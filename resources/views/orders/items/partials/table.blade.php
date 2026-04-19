{{-- FILE: resources/views/orders/items/partials/table.blade.php | V8 --}}

@php
    use App\Support\Catalogs\OrderCatalog;
    use App\Support\Catalogs\ProductCatalog;

    $order = $order ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en esta orden.';
    $trailQuery = $trailQuery ?? [];

    $orderIsReadonly = $order ? OrderCatalog::isReadonlyStatus($order->status) : false;
@endphp

@if ($items->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Ítem</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Total línea</th>
                    <th class="compact-actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $canEdit = !$orderIsReadonly && !$item->hasInventoryMovements();
                        $canDelete = !$orderIsReadonly && !$item->hasInventoryMovements();
                        $itemTypeLabel = ProductCatalog::kindLabel($item->kind);
                    @endphp

                    <tr>
                        <td>{{ $item->position }}</td>

                        <td>
                            <div>{{ $item->description }}</div>
                            <div class="text-muted">{{ $itemTypeLabel }}</div>
                        </td>

                        <td>{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>

                        <td>${{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>

                        <td>${{ number_format((float) $item->subtotal, 2, ',', '.') }}</td>

                        <td class="compact-actions-cell">
                            @can('update', $order)
                                <div class="compact-actions">
                                    @if ($canEdit)
                                        <x-button-tool :href="route(
                                            'orders.items.edit',
                                            ['order' => $order, 'item' => $item] + $trailQuery,
                                        )" title="Editar ítem" label="Editar ítem">
                                            <x-icons.pencil />
                                        </x-button-tool>
                                    @endif

                                    @if ($canDelete)
                                        <x-button-tool-submit :action="route(
                                            'orders.items.destroy',
                                            ['order' => $order, 'item' => $item] + $trailQuery,
                                        )" method="DELETE" variant="danger"
                                            title="Eliminar ítem" label="Eliminar ítem"
                                            message="¿Deseas eliminar este ítem?">
                                            <x-icons.trash />
                                        </x-button-tool-submit>
                                    @endif

                                    @if (!$canEdit && !$canDelete)
                                        <span class="text-muted">—</span>
                                    @endif

                                    @if (!$canEdit && !$canDelete)
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">—</span>
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
