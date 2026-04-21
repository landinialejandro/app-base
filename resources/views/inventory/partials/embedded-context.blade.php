{{-- FILE: resources/views/inventory/partials/embedded-context.blade.php | V5 --}}

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
                                $executeKind = $row['execute_kind'] ?? null;
                                $pendingQuantity = (float) ($row['pending_quantity'] ?? 0);
                                $executedQuantity = (float) ($row['executed_quantity'] ?? 0);
                                $currentStock = array_key_exists('current_stock', $row)
                                    ? (float) $row['current_stock']
                                    : null;
                                $direction = $row['direction'] ?? 'out';
                                $executeLabel = $direction === 'in' ? 'Recibir' : 'Surtir';
                                $lineStatusLabel = $row['line_status_label'] ?? 'Pendiente';
                                $lineStatusBadge = $row['line_status_badge'] ?? 'status-badge--pending';
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
                                    @if ($canExecute && $executeKind && $pendingQuantity > 0)
                                        <form method="POST"
                                            action="{{ route('inventory.movements.store', $trailQuery) }}"
                                            class="inline-form" data-action="app-confirm-submit"
                                            data-confirm-message="¿Deseas {{ strtolower($executeLabel) }} esta línea por {{ number_format($pendingQuantity, 2, ',', '.') }}?">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $row['product_id'] }}">
                                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                                            <input type="hidden" name="order_item_id"
                                                value="{{ $row['order_item_id'] }}">
                                            <input type="hidden" name="kind" value="{{ $executeKind }}">
                                            <input type="hidden" name="quantity"
                                                value="{{ number_format($pendingQuantity, 2, '.', '') }}">
                                            <input type="hidden" name="return_context" value="orders.show">

                                            <button type="submit" class="btn btn-secondary btn-sm"
                                                title="{{ $executeLabel }} línea"
                                                aria-label="{{ $executeLabel }} línea">
                                                {{ $executeLabel }}
                                            </button>
                                        </form>
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
