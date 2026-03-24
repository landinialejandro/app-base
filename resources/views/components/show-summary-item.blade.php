{{-- FILE: resources/views/components/show-summary-item.blade.php | V2 --}}

@props(['label', 'help' => null, 'span' => 1])

@php
    $span = match ((string) $span) {
        '2', 'double' => 2,
        '3', 'triple' => 3,
        'full' => 'full',
        default => 1,
    };
@endphp

<div
    {{ $attributes->class([
        'summary-inline-card',
        'show-summary-item',
        'show-summary-item--span-2' => $span === 2,
        'show-summary-item--span-3' => $span === 3,
        'show-summary-item--span-full' => $span === 'full',
    ]) }}>
    <div class="summary-inline-label">{{ $label }}</div>

    <div class="summary-inline-value">
        {{ $slot }}
    </div>

    @if (filled($help))
        <div class="summary-inline-help">{{ $help }}</div>
    @endif
</div>
