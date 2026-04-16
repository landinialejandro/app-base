{{-- FILE: resources/views/orders/items/partials/table.blade.php | V5 --}}

@php
    use App\Support\Catalogs\ProductCatalog;

    $order = $order ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en esta orden.';
    $trailQuery = $trailQuery ?? [];
    $inventoryContext = $inventoryContext ?? null;

    $inventoryRows = collect($inventoryContext['items'] ?? [])->keyBy('order_item_id');
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
                    <th>Stock</th>
                    <th>Ejecutado</th>
                    <th>Pendiente</th>
                    <th>Estado</th>
                    <th>Precio unitario</th>
                    <th>Total línea</th>
                    <th class="compact-actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $inventoryRow = $inventoryRows->get($item->id, []);
                        $isPhysicalProduct =
                            ($inventoryRow['is_physical_product'] ?? false) === true ||
                            $item->kind === ProductCatalog::KIND_PRODUCT;

                        $canEdit = ($inventoryRow['can_edit'] ?? true) === true;
                        $canDelete = ($inventoryRow['can_delete'] ?? true) === true;
                        $canExecute = ($inventoryRow['can_execute'] ?? false) === true;

                        $executeKind = $inventoryRow['execute_kind'] ?? null;
                        $pendingQuantity = (float) ($inventoryRow['pending_quantity'] ?? 0);
                        $direction = $inventoryRow['direction'] ?? 'out';
                        $executeLabel = $direction === 'in' ? 'Recibir' : 'Surtir';
                    @endphp

                    <tr>
                        <td>{{ $item->position }}</td>
                        <td>{{ ProductCatalog::kindLabel($item->kind) }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>

                        <td>
                            @if ($isPhysicalProduct && array_key_exists('current_stock', $inventoryRow))
                                {{ number_format((float) $inventoryRow['current_stock'], 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @if ($isPhysicalProduct)
                                {{ number_format((float) ($inventoryRow['executed_quantity'] ?? 0), 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @if ($isPhysicalProduct)
                                {{ number_format($pendingQuantity, 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @if ($isPhysicalProduct)
                                <span
                                    class="status-badge {{ $inventoryRow['line_status_badge'] ?? 'status-badge--pending' }}">
                                    {{ $inventoryRow['line_status_label'] ?? 'Pendiente' }}
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        <td>${{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                        <td>${{ number_format((float) $item->subtotal, 2, ',', '.') }}</td>

                        <td class="compact-actions-cell">
                            @can('update', $order)
                                <div class="compact-actions">
                                    @if ($canEdit)
                                        <a href="{{ route('orders.items.edit', ['order' => $order, 'item' => $item] + $trailQuery) }}"
                                            class="btn btn-secondary btn-icon" title="Editar ítem" aria-label="Editar ítem">
                                            <x-icons.pencil />
                                        </a>
                                    @endif

                                    @if ($canDelete)
                                        <form method="POST"
                                            action="{{ route('orders.items.destroy', ['order' => $order, 'item' => $item] + $trailQuery) }}"
                                            class="inline-form" data-action="app-confirm-submit"
                                            data-confirm-message="¿Deseas eliminar este ítem?">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-icon" title="Eliminar ítem"
                                                aria-label="Eliminar ítem">
                                                <x-icons.trash />
                                            </button>
                                        </form>
                                    @endif

                                    @if ($canExecute && $executeKind && $pendingQuantity > 0)
                                        <form method="POST" action="{{ route('inventory.movements.store', $trailQuery) }}"
                                            class="inline-form" data-action="app-confirm-submit"
                                            data-confirm-message="¿Deseas {{ strtolower($executeLabel) }} esta línea por {{ number_format($pendingQuantity, 2, ',', '.') }}?">
                                            @csrf

                                            <input type="hidden" name="product_id"
                                                value="{{ $inventoryRow['product_id'] }}">
                                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                                            <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                            <input type="hidden" name="kind" value="{{ $executeKind }}">
                                            <input type="hidden" name="quantity"
                                                value="{{ number_format($pendingQuantity, 2, '.', '') }}">
                                            <input type="hidden" name="return_context" value="orders.show">

                                            <button type="submit" class="btn btn-secondary btn-sm"
                                                title="{{ $executeLabel }} línea" aria-label="{{ $executeLabel }} línea">
                                                {{ $executeLabel }}
                                            </button>
                                        </form>
                                    @endif

                                    @if (!$canEdit && !$canDelete && !$canExecute)
                                        —
                                    @endif
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
