{{-- FILE: resources/views/inventory/partials/movements-table.blade.php | V2 --}}

@php
    $movements = ($movements ?? collect())->values();
    $emptyMessage = $emptyMessage ?? 'No hay movimientos para mostrar.';
    $trailQuery = $trailQuery ?? [];
@endphp

@if ($movements->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Producto</th>
                    <th>Línea</th>
                    <th>Cantidad</th>
                    <th>Orden</th>
                    <th>Documento</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ ucfirst($movement->kind) }}</td>
                        <td>{{ $movement->product?->name ?? '—' }}</td>
                        <td>
                            @if ($movement->orderItem)
                                #{{ $movement->orderItem->id }}
                                @if ($movement->orderItem->description)
                                    — {{ $movement->orderItem->description }}
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ number_format((float) $movement->quantity, 2, ',', '.') }}</td>
                        <td>
                            @if ($movement->order)
                                <a href="{{ route('orders.show', ['order' => $movement->order] + $trailQuery) }}">
                                    {{ $movement->order->number ?: 'Orden #' . $movement->order->id }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($movement->document)
                                <a
                                    href="{{ route('documents.show', ['document' => $movement->document] + $trailQuery) }}">
                                    {{ $movement->document->number ?: 'Documento #' . $movement->document->id }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $movement->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
