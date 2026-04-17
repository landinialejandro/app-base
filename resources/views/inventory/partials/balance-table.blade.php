{{-- FILE: resources/views/inventory/partials/balance-table.blade.php | V2 --}}

@php
    $rows = ($rows ?? collect())->values();
    $trailQuery = $trailQuery ?? [];
@endphp

@if ($rows->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>SKU</th>
                    <th>Unidad</th>
                    <th>Stock actual</th>
                    <th>Movimientos</th>
                    <th class="compact-actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $product = $row['product'];
                        $stock = (float) ($row['stock'] ?? 0);
                        $movementCount = (int) ($row['movement_count'] ?? 0);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('inventory.show', ['product' => $product] + $trailQuery) }}">
                                {{ $product->name }}
                            </a>
                        </td>

                        <td>{{ $product->sku ?: '—' }}</td>

                        <td>{{ $product->unit_label ?: '—' }}</td>

                        <td>{{ number_format($stock, 2, ',', '.') }}</td>

                        <td>{{ $movementCount }}</td>

                        <td class="compact-actions-cell">
                            <div class="compact-actions">
                                <a href="{{ route('inventory.show', ['product' => $product] + $trailQuery) }}"
                                    class="btn btn-secondary btn-sm" title="Ver ficha de inventario"
                                    aria-label="Ver ficha de inventario">
                                    Ver ficha
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">No hay productos stockeables para mostrar.</p>
@endif
