{{-- FILE: resources/views/products/composition/summary.blade.php | V7 --}}

@php
    $composition = $composition ?? [
        'has_components' => false,
        'components_count' => 0,
        'components' => collect(),
    ];

    $components = collect($composition['components'] ?? []);
    $componentsCount = (int) ($composition['components_count'] ?? $components->count());

    $visibleComponents = $componentsCount > 3
        ? $components->take(2)
        : $components->take(3);

    $hiddenCount = max(0, $componentsCount - $visibleComponents->count());
@endphp

@if (($composition['has_components'] ?? false) && $components->isNotEmpty())
    <div class="summary-badge-stack">
        @foreach ($visibleComponents as $summaryComponent)
            @php
                $componentProduct = $summaryComponent['product'] ?? [];
                $quantity = $summaryComponent['quantity'] ?? 0;
                $unitLabel = $summaryComponent['unit_label'] ?? null;
                $name = $componentProduct['name'] ?? 'Componente';
            @endphp

            <span class="status-badge status-badge--neutral">
                {{ $name }}
                · {{ number_format((float) $quantity, 2, ',', '.') }}{{ $unitLabel ? ' '.$unitLabel : '' }}
            </span>
        @endforeach

        @if ($hiddenCount > 0)
            <span class="status-badge status-badge--neutral">
                +{{ $hiddenCount }} componentes
            </span>
        @endif
    </div>
@else
    <span class="text-muted">Sin composición definida</span>
@endif