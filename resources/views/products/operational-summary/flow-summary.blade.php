{{-- FILE: resources/views/products/operational-summary/flow-summary.blade.php | V3 --}}

@php
    $inventory = $inventory ?? [];

    $formatQuantity = fn ($value): string => number_format((float) ($value ?? 0), 2, ',', '.');
@endphp

<div class="detail-mini">
    @if (($inventory['applies'] ?? false) === true)
        <div>
            <strong>
                {{ (int) ($inventory['movements_count'] ?? 0) }} movimientos
            </strong>
        </div>

        <div class="text-muted">
            Entradas: {{ $formatQuantity($inventory['entries_total'] ?? 0) }}
            {{ $unitLabel ?? '' }}
        </div>

        <div class="text-muted">
            Salidas: {{ $formatQuantity($inventory['exits_total'] ?? 0) }}
            {{ $unitLabel ?? '' }}
        </div>
    @else
        <div>
            <strong>No aplica</strong>
        </div>

        <div class="text-muted">
            Producto no stockeable.
        </div>
    @endif
</div>