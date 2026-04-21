{{-- FILE: resources/views/inventory/partials/movements-table.blade.php | V7 --}}

@php
    use App\Support\Inventory\InventoryMovementService;

    $movementRows = ($movementRows ?? collect())->values();
    $emptyMessage = $emptyMessage ?? 'No hay movimientos para mostrar.';
    $trailQuery = $trailQuery ?? [];

    $kindLabels = [
        InventoryMovementService::KIND_INGRESAR => 'Ingresar',
        InventoryMovementService::KIND_CONSUMIR => 'Consumir',
        InventoryMovementService::KIND_ENTREGAR => 'Entregar',
    ];
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
                        <th>Origen</th>
                        <th>Cantidad</th>
                        <th>Saldo</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movementRows as $row)
                        @php
                            $movement = $row['movement'];
                            $signedQuantity = (float) ($row['signed_quantity'] ?? 0);
                            $runningBalance = (float) ($row['running_balance'] ?? 0);

                            $originLabel = 'Ajuste manual';
                            $originUrl = null;

                            if ($movement->order) {
                                $originLabel = $movement->order->number ?: 'Orden #' . $movement->order->id;
                                $originUrl = route('orders.show', ['order' => $movement->order] + $trailQuery);
                            } elseif ($movement->document) {
                                $originLabel = $movement->document->number ?: 'Documento #' . $movement->document->id;
                                $originUrl = route('documents.show', ['document' => $movement->document] + $trailQuery);
                            }
                        @endphp

                        <tr>
                            <td>#{{ $movement->id }}</td>

                            <td>{{ $movement->created_at?->format('d/m/Y H:i') ?? '—' }}</td>

                            <td>{{ $kindLabels[$movement->kind] ?? ucfirst($movement->kind) }}</td>

                            <td>
                                @if ($originUrl)
                                    <a href="{{ $originUrl }}">{{ $originLabel }}</a>
                                @else
                                    {{ $originLabel }}
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

                            <td>{{ $movement->notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
