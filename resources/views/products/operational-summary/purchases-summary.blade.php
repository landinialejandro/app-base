{{-- FILE: resources/views/products/operational-summary/purchases-summary.blade.php | V3 --}}

@php
    $purchases = $purchases ?? [];

    $formatMoney = fn ($value): string => $value !== null
        ? '$' . number_format((float) $value, 2, ',', '.')
        : '—';

    $formatQuantity = fn ($value): string => number_format((float) ($value ?? 0), 2, ',', '.');

    $last = $purchases['last'] ?? null;
    $previous = $purchases['previous_supplier'] ?? null;
@endphp

<div class="detail-mini">
    <div>
        <strong>
            {{ $formatQuantity($purchases['quantity_total'] ?? 0) }}
            {{ $unitLabel ?? '' }}
        </strong>
    </div>

    <div class="text-muted">
        Promedio: {{ $formatMoney($purchases['unit_price_avg'] ?? null) }}
    </div>

    <div class="text-muted">
        Último proveedor: {{ $last['party_name'] ?? '—' }}
    </div>

    <div class="text-muted">
        Anterior: {{ $previous['party_name'] ?? '—' }}
    </div>
</div>