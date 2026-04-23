{{-- FILE: resources/views/orders/items/partials/table.blade.php | V11 --}}

@php
    use App\Support\Catalogs\OrderCatalog;
    use App\Support\Catalogs\OrderItemCatalog;
    use App\Support\Catalogs\ProductCatalog;
    use App\Support\Inventory\InventorySurfaceService;
    use App\Support\Modules\ModuleSurfaceRegistry;

    $order = $order ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en esta orden.';
    $trailQuery = $trailQuery ?? [];

    $orderIsReadonly = $order ? OrderCatalog::isReadonlyStatus($order->status) : false;
    $modalNamespace = 'order-items';
@endphp

@if ($items->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Ítem</th>
                    <th>Estado</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Total línea</th>
                    <th class="compact-actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $canEdit =
                            !$orderIsReadonly &&
                            !in_array(
                                $item->status,
                                [OrderItemCatalog::STATUS_COMPLETED, OrderItemCatalog::STATUS_CANCELLED],
                                true,
                            );

                        $canDelete =
                            !$orderIsReadonly &&
                            !in_array(
                                $item->status,
                                [OrderItemCatalog::STATUS_COMPLETED, OrderItemCatalog::STATUS_CANCELLED],
                                true,
                            );

                        $itemTypeLabel = ProductCatalog::kindLabel($item->kind);

                        $lineStatus = $item->status ?: OrderItemCatalog::STATUS_PENDING;
                        $lineStatusLabel = OrderItemCatalog::statusLabel($lineStatus);
                        $lineStatusBadge = OrderItemCatalog::badgeClass($lineStatus);

                        $rowHostPack = app(InventorySurfaceService::class)->hostPack('orders.items.row', $item, [
                            'order' => $order,
                            'trailQuery' => $trailQuery,
                            'modal_namespace' => $modalNamespace,
                        ]);

                        $rowLinkedActions = collect(
                            app(ModuleSurfaceRegistry::class)->linkedFor('orders.items.row', $rowHostPack)
                        )
                            ->where('slot', 'row_actions')
                            ->values();
                    @endphp

                    <tr>
                        <td>{{ $item->position }}</td>

                        <td>
                            <div>{{ $item->description }}</div>
                            <div class="text-muted">{{ $itemTypeLabel }}</div>
                        </td>

                        <td>
                            <span class="status-badge {{ $lineStatusBadge }}">
                                {{ $lineStatusLabel }}
                            </span>
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

                                    @foreach ($rowLinkedActions as $surface)
                                        @include($surface['view'], $surface['data'] ?? [])
                                    @endforeach

                                    @if (!$canEdit && !$canDelete && $rowLinkedActions->isEmpty())
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            @else
                                @if ($rowLinkedActions->isNotEmpty())
                                    <div class="compact-actions">
                                        @foreach ($rowLinkedActions as $surface)
                                            @include($surface['view'], $surface['data'] ?? [])
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
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