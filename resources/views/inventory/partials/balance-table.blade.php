{{-- FILE: resources/views/inventory/partials/balance-table.blade.php | V1 --}}

@php
    $rows = $rows ?? collect();
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
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $product = $row['product'];
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('inventory.show', ['product' => $product] + $trailQuery) }}">
                                {{ $product->name }}
                            </a>
                        </td>
                        <td>{{ $product->sku ?: '—' }}</td>
                        <td>{{ $product->unit_label ?: '—' }}</td>
                        <td>{{ number_format((float) $row['stock'], 2, ',', '.') }}</td>
                        <td>{{ $row['movement_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">No hay productos stockeables para mostrar.</p>
@endif
