{{-- FILE: resources/views/components/analytics/card.blade.php | V1 --}}

@props([
    'title' => null,
    'span' => 1,
])

@php
    $span = match ((string) $span) {
        '2', 'double' => 2,
        '3', 'triple', 'full' => 3,
        default => 1,
    };
@endphp

<div
    {{ $attributes->class([
        'analytics-card',
        'analytics-card--span-2' => $span === 2,
        'analytics-card--span-3' => $span === 3,
    ]) }}>
    @if (filled($title))
        <div class="analytics-card__title">{{ $title }}</div>
    @endif

    <div class="analytics-card__body">
        {{ $slot }}
    </div>
</div>
