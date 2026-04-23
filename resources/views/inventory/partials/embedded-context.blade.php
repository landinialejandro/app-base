{{-- FILE: resources/views/inventory/partials/embedded-context.blade.php | V9 --}}

@php
    use App\Support\Inventory\InventoryMovementService;

    $contextType = $contextType ?? 'order';
    $trailQuery = $trailQuery ?? [];
@endphp

@if ($contextType === 'product')
    @php
        $product = $product ?? null;
        $movementRows = ($movementRows ?? collect())->values();
        $movementKind = $movementKind ?? '';
        $kindTabs = $kindTabs ?? [];
        $emptyMessage = $emptyMessage ?? 'No hay movimientos registrados para este artículo.';
    @endphp

    @if (!empty($kindTabs))
        <x-tab-toolbar label="Tipos de movimiento">
            <x-slot:tabs>
                <x-horizontal-scroll label="Tipos de movimiento">
                    @foreach ($kindTabs as $tab)
                        <a href="{{ $tab['url'] }}"
                            class="tabs-link {{ $tab['is_active'] ?? false ? 'is-active' : '' }}"
                            aria-current="{{ $tab['is_active'] ?? false ? 'page' : 'false' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </x-horizontal-scroll>
            </x-slot:tabs>
        </x-tab-toolbar>
    @endif

    @include('inventory.partials.movements-table', [
        'movementRows' => $movementRows,
        'emptyMessage' => $emptyMessage,
        'trailQuery' => $trailQuery,
    ])
@else
    @php
        $order = $order ?? null;
        $inventoryContext = $inventoryContext ?? [];

        $items = collect($inventoryContext['items'] ?? [])->keyBy('order_item_id');
    @endphp

    @if ($items->isNotEmpty())
        <x-card class="list-card">
            <div class="table-wrap list-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pos.</th>
                            <th>Ítem</th>
                            <th>Stock</th>
                            <th>Ejecutado</th>
                            <th>Pendiente</th>
                            <th>Estado</th>
                            <th class="compact-actions-cell">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $row)
                            @php
                                $isPhysical = ($row['is_physical_product'] ?? false) === true;
                                $canExecute = ($row['can_execute'] ?? false) === true;
                                $canReturn = ($row['can_return'] ?? false) === true;

                                $pendingQuantity = (float) ($row['pending_quantity'] ?? 0);
                                $executedQuantity = (float) ($row['executed_quantity'] ?? 0);
                                $maxReturnQuantity = (float) ($row['max_return_quantity'] ?? 0);

                                $currentStock = array_key_exists('current_stock', $row)
                                    ? (float) $row['current_stock']
                                    : null;

                                $lineStatusLabel = $row['line_status_label'] ?? 'Pendiente';
                                $lineStatusBadge = $row['line_status_badge'] ?? 'status-badge--pending';

                                $executeModalId = 'inventory-execute-line-' . ($row['order_item_id'] ?? $loop->index);
                                $returnModalId = 'inventory-return-line-' . ($row['order_item_id'] ?? $loop->index);

                                $productId = $row['product_id'] ?? null;
                                $orderItemId = $row['order_item_id'] ?? null;

                                $lineMovementsUrl =
                                    $productId && $orderItemId
                                        ? route(
                                            'inventory.show',
                                            ['product' => $productId] +
                                                $trailQuery + [
                                                    'order_item_id' => $orderItemId,
                                                ],
                                        )
                                        : null;
                            @endphp

                            <tr>
                                <td>{{ $row['position'] ?? '—' }}</td>

                                <td>{{ $row['description'] ?? '—' }}</td>

                                <td>
                                    @if ($isPhysical && $currentStock !== null)
                                        {{ number_format($currentStock, 2, ',', '.') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($isPhysical)
                                        {{ number_format($executedQuantity, 2, ',', '.') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($isPhysical)
                                        {{ number_format($pendingQuantity, 2, ',', '.') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($isPhysical)
                                        <span class="status-badge {{ $lineStatusBadge }}">
                                            {{ $lineStatusLabel }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="compact-actions-cell">
                                    @if ($isPhysical && ($canExecute || $canReturn || $lineMovementsUrl))
                                        <div class="compact-actions">
                                            @if ($canExecute && $pendingQuantity > 0)
                                                <button type="button"
                                                    class="{{ $row['execute_button_class'] ?? 'btn btn-success btn-icon' }}"
                                                    data-action="app-modal-open"
                                                    data-modal-target="#{{ $executeModalId }}"
                                                    title="{{ $row['execute_title'] ?? 'Operar línea' }}"
                                                    aria-label="{{ $row['execute_title'] ?? 'Operar línea' }}">

                                                    @if (($row['execute_icon'] ?? 'truck') === 'plus')
                                                        <x-icons.plus />
                                                    @else
                                                        <x-icons.truck />
                                                    @endif
                                                </button>

                                                @include('inventory.partials.order-line-execute-modal', [
                                                    'order' => $order,
                                                    'row' => $row,
                                                    'trailQuery' => $trailQuery,
                                                    'modalId' => $executeModalId,
                                                ])
                                            @endif

                                            @if ($canReturn && $maxReturnQuantity > 0)
                                                <button type="button"
                                                    class="{{ $row['return_button_class'] ?? 'btn btn-warning btn-icon' }}"
                                                    data-action="app-modal-open"
                                                    data-modal-target="#{{ $returnModalId }}"
                                                    title="{{ $row['return_title'] ?? 'Revertir línea' }}"
                                                    aria-label="{{ $row['return_title'] ?? 'Revertir línea' }}">
                                                    <x-icons.rotate-ccw />
                                                </button>

                                                @include('inventory.partials.order-line-return-modal', [
                                                    'order' => $order,
                                                    'row' => $row,
                                                    'trailQuery' => $trailQuery,
                                                    'modalId' => $returnModalId,
                                                ])
                                            @endif

                                            @if ($lineMovementsUrl)
                                                <a href="{{ $lineMovementsUrl }}" class="btn btn-secondary btn-icon"
                                                    title="Ver movimientos de la línea"
                                                    aria-label="Ver movimientos de la línea">
                                                    <x-icons.eye />
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @else
        <x-card>
            <p class="mb-0">No hay líneas operativas disponibles para esta orden.</p>
        </x-card>
    @endif
@endif
