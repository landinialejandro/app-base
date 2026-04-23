{{-- FILE: resources/views/inventory/partials/embedded-context.blade.php | V11 --}}

@php
    use App\Support\Inventory\InventoryMovementService;
    use App\Support\Inventory\InventorySurfaceService;
    use App\Support\Modules\ModuleSurfaceRegistry;

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
        $items = collect($inventoryContext['items'] ?? [])->values();
        $modalNamespace = 'inventory-embedded';
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
                                $currentStock = array_key_exists('current_stock', $row)
                                    ? (float) $row['current_stock']
                                    : null;
                                $executedQuantity = (float) ($row['executed_quantity'] ?? 0);
                                $pendingQuantity = (float) ($row['pending_quantity'] ?? 0);
                                $lineStatusLabel = $row['line_status_label'] ?? 'Pendiente';
                                $lineStatusBadge = $row['line_status_badge'] ?? 'status-badge--pending';

                                $rowActions = collect();

                                if ($order && !empty($row['order_item_id'])) {
                                    $orderItemModel = $order->items->firstWhere('id', (int) $row['order_item_id']);

                                    if ($orderItemModel) {
                                        $rowHostPack = app(InventorySurfaceService::class)->hostPack('orders.items.row', $orderItemModel, [
                                            'order' => $order,
                                            'trailQuery' => $trailQuery,
                                            'modal_namespace' => $modalNamespace,
                                        ]);

                                        $rowActions = collect(
                                            app(ModuleSurfaceRegistry::class)->linkedFor('orders.items.row', $rowHostPack)
                                        )
                                            ->where('slot', 'row_actions')
                                            ->sortBy(fn ($item) => $item['priority'] ?? 999)
                                            ->values();
                                    }
                                }
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
                                    @if ($isPhysical && $rowActions->isNotEmpty())
                                        <div class="compact-actions">
                                            @foreach ($rowActions as $surface)
                                                @include($surface['view'], $surface['data'] ?? [])
                                            @endforeach
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