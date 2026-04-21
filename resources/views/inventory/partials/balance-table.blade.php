{{-- FILE: resources/views/inventory/partials/balance-table.blade.php | V5 --}}

@php
    $rows = ($rows ?? collect())->values();
    $trailQuery = $trailQuery ?? [];
@endphp

@if ($rows->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Artículo</th>
                    <th>SKU</th>
                    <th>Unidad</th>
                    <th>Ingresos</th>
                    <th>Egresos</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $product = $row['product'];
                        $totalIn = (float) ($row['total_in'] ?? 0);
                        $totalOut = (float) ($row['total_out'] ?? 0);
                        $balance = (float) ($row['balance'] ?? 0);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('inventory.show', ['product' => $product] + $trailQuery) }}">
                                {{ $product->name }}
                            </a>
                        </td>

                        <td>{{ $product->sku ?: '—' }}</td>

                        <td>{{ $product->unit_label ?: '—' }}</td>

                        <td>{{ number_format($totalIn, 2, ',', '.') }}</td>

                        <td>{{ number_format($totalOut, 2, ',', '.') }}</td>

                        <td>{{ number_format($balance, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">No hay artículos stockeables para mostrar.</p>
@endif
