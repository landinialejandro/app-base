{{-- FILE: resources/views/components/analytics/pie-chart.blade.php | V2 --}}

@props([
    'total' => null,
    'centerLabel' => '',
    'segments' => [],
    'emptyMessage' => 'No hay datos suficientes para graficar.',
    'wide' => false,
    'showLegend' => true,
])

@php
    $segments = collect($segments)
        ->map(function ($segment) {
            return [
                'label' => $segment['label'] ?? '—',
                'count' => (int) ($segment['count'] ?? 0),
                'segment_class' => $segment['segment_class'] ?? ($segment['class'] ?? ''),
                'dot_class' => $segment['dot_class'] ?? '',
            ];
        })
        ->filter(fn($segment) => $segment['count'] > 0)
        ->values();

    $resolvedTotal = $total ?? $segments->sum('count');
    $resolvedTotal = (int) $resolvedTotal;

    $offset = 0;

    $computedSegments = $segments->map(function ($segment) use ($resolvedTotal, &$offset) {
        $percent = $resolvedTotal > 0 ? round(($segment['count'] / $resolvedTotal) * 100, 2) : 0;

        $computed = [
            ...$segment,
            'percent' => $percent,
            'dash' => $percent . ' ' . (100 - $percent),
            'offset' => -$offset,
        ];

        $offset += $percent;

        return $computed;
    });

    $hasData = $resolvedTotal > 0 && $computedSegments->isNotEmpty();

    $layoutClasses = ['pie-layout'];

    if ($wide) {
        $layoutClasses[] = 'pie-layout--wide';
    }

    if (!$showLegend) {
        $layoutClasses[] = 'pie-layout--no-legend';
    }
@endphp

@if ($hasData)
    <div {{ $attributes->class($layoutClasses) }}>
        <div class="pie-chart-wrap">
            <svg viewBox="0 0 42 42" class="pie-chart" aria-hidden="true">
                <circle class="pie-track" cx="21" cy="21" r="15.9155"></circle>

                @foreach ($computedSegments as $segment)
                    <circle class="pie-segment {{ $segment['segment_class'] }}" cx="21" cy="21" r="15.9155"
                        stroke-dasharray="{{ $segment['dash'] }}" stroke-dashoffset="{{ $segment['offset'] }}"></circle>
                @endforeach
            </svg>

            <div class="pie-center">
                <strong>{{ $resolvedTotal }}</strong>

                @if (filled($centerLabel))
                    <span>{{ $centerLabel }}</span>
                @endif
            </div>
        </div>

        @if ($showLegend)
            <div class="pie-legend">
                @foreach ($computedSegments as $segment)
                    <div class="pie-legend-item">
                        <span class="pie-dot {{ $segment['dot_class'] }}"></span>
                        <span>{{ $segment['label'] }}: {{ $segment['count'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
