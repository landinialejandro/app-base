{{-- FILE: resources/views/inventory/partials/movements-table.blade.php | V13 --}}

@php
    use App\Models\Document;
    use App\Models\Order;
    use App\Models\OrderItem;
    use App\Support\Inventory\InventoryMovementService;
    use App\Support\Inventory\InventoryOperationCatalog;
    use App\Support\Inventory\InventoryOriginCatalog;

    $movementRows = ($movementRows ?? collect())->values();
    $emptyMessage = $emptyMessage ?? 'No hay movimientos para mostrar.';
    $trailQuery = $trailQuery ?? [];

    $kindLabels = [
        InventoryMovementService::KIND_INGRESAR => 'Ingresar',
        InventoryMovementService::KIND_CONSUMIR => 'Consumir',
        InventoryMovementService::KIND_ENTREGAR => 'Entregar',
    ];

    $extractTraceValue = function (?string $notes, string $label): ?string {
        if (! $notes) {
            return null;
        }

        foreach (explode('|', $notes) as $part) {
            $part = trim($part);

            if (str_starts_with($part, $label . ':')) {
                $value = trim(substr($part, strlen($label) + 1));

                return $value !== '' ? $value : null;
            }
        }

        return null;
    };

    $resolveOrigin = function ($movement) use ($trailQuery): array {
        if ($movement->origin_type === InventoryOriginCatalog::TYPE_ORDER && $movement->origin_id) {
            $order = Order::query()
                ->where('tenant_id', $movement->tenant_id)
                ->whereKey($movement->origin_id)
                ->first();

            if ($order) {
                return [
                    'label' => $order->number ?: 'Orden #' . $order->id,
                    'url' => route('orders.show', ['order' => $order] + $trailQuery),
                ];
            }
        }

        if ($movement->origin_type === InventoryOriginCatalog::TYPE_DOCUMENT && $movement->origin_id) {
            $document = Document::query()
                ->where('tenant_id', $movement->tenant_id)
                ->whereKey($movement->origin_id)
                ->first();

            if ($document) {
                return [
                    'label' => $document->number ?: 'Documento #' . $document->id,
                    'url' => route('documents.show', ['document' => $document] + $trailQuery),
                ];
            }
        }

        if ($movement->origin_type === InventoryOriginCatalog::TYPE_MANUAL || empty($movement->origin_type)) {
            return [
                'label' => 'Manual',
                'url' => null,
            ];
        }

        return [
            'label' => $movement->origin_type . ($movement->origin_id ? ' #' . $movement->origin_id : ''),
            'url' => null,
        ];
    };

    $resolveLineLabel = function ($movement): ?string {
        if ($movement->origin_line_type === InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM && $movement->origin_line_id) {
            $item = OrderItem::query()
                ->where('tenant_id', $movement->tenant_id)
                ->whereKey($movement->origin_line_id)
                ->first();

            if ($item) {
                return '#' . ($item->position ?? $item->id) . ' — ' . $item->description;
            }

            return 'order_item #' . $movement->origin_line_id;
        }

        if ($movement->origin_line_type && $movement->origin_line_id) {
            return $movement->origin_line_type . ' #' . $movement->origin_line_id;
        }

        return null;
    };
@endphp

@if ($movementRows->count())
    <x-card class="list-card">
        <div class="table-wrap list-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Operación</th>
                        <th>Origen</th>
                        <th>Cantidad</th>
                        <th>Saldo</th>
                        <th>Notas</th>
                        <th class="compact-actions-cell">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movementRows as $row)
                        @php
                            $movement = $row['movement'];
                            $operation = $movement->operation;
                            $signedQuantity = (float) ($row['signed_quantity'] ?? 0);
                            $runningBalance = (float) ($row['running_balance'] ?? 0);

                            $origin = $resolveOrigin($movement);
                            $lineLabel = $resolveLineLabel($movement);

                            $actorLabel = $extractTraceValue($movement->notes, 'Usuario');
                            $userNote = $extractTraceValue($movement->notes, 'Nota usuario');

                            $hasVisibleNotes = $actorLabel || $lineLabel || $userNote;
                        @endphp

                        <tr>
                            <td>#{{ $movement->id }}</td>

                            <td>{{ $movement->created_at?->format('d/m/Y H:i') ?? '—' }}</td>

                            <td>{{ $kindLabels[$movement->kind] ?? ucfirst($movement->kind) }}</td>

                            <td>
                                @if ($operation)
                                    #{{ $operation->id }}
                                    <div class="text-muted small">
                                        {{ InventoryOperationCatalog::label($operation->operation_type) }}
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if ($origin['url'])
                                    <a href="{{ $origin['url'] }}">{{ $origin['label'] }}</a>
                                @else
                                    {{ $origin['label'] }}
                                @endif
                            </td>

                            <td>
                                @if ($signedQuantity > 0)
                                    +{{ number_format($signedQuantity, 2, ',', '.') }}
                                @elseif ($signedQuantity < 0)
                                    {{ number_format($signedQuantity, 2, ',', '.') }}
                                @else
                                    {{ number_format(0, 2, ',', '.') }}
                                @endif
                            </td>

                            <td>{{ number_format($runningBalance, 2, ',', '.') }}</td>

                            <td>
                                @if ($hasVisibleNotes)
                                    @if ($actorLabel)
                                        <span class="text-muted">Usr:</span> {{ $actorLabel }}<br>
                                    @endif

                                    @if ($lineLabel)
                                        <span class="text-muted">Lín:</span> {{ $lineLabel }}<br>
                                    @endif

                                    @if ($userNote)
                                        <span class="text-muted">Nota:</span> {{ $userNote }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>

                            <td class="compact-actions-cell">
                                <div class="compact-actions">
                                    <x-button-tool
                                        :href="route('inventory.movements.show', ['movement' => $movement] + $trailQuery)"
                                        title="Ver movimiento"
                                        label="Ver movimiento"
                                        variant="secondary"
                                    >
                                        <x-icons.eye />
                                    </x-button-tool>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif