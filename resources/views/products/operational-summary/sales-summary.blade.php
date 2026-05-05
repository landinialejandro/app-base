{{-- FILE: resources/views/products/operational-summary/sales-summary.blade.php | V3 --}}

@php
    $sales = $sales ?? [];

    $formatMoney = fn ($value): string => $value !== null
        ? '$' . number_format((float) $value, 2, ',', '.')
        : '—';

    $formatQuantity = fn ($value): string => number_format((float) ($value ?? 0), 2, ',', '.');

    $last = $sales['last'] ?? null;
@endphp

<div class="detail-mini">
    <div>
        <strong>
            {{ $formatQuantity($sales['quantity_total'] ?? 0) }}
            {{ $unitLabel ?? '' }}
        </strong>
    </div>

    <div class="text-muted">
        Promedio: {{ $formatMoney($sales['unit_price_avg'] ?? null) }}
    </div>

    <div class="text-muted">
        Última venta: {{ $formatMoney($last['unit_price'] ?? null) }}
    </div>
</div>