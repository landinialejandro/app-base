{{-- FILE: resources/views/components/show-summary-item.blade.php --}}

@props(['label', 'help' => null])

<div {{ $attributes->merge(['class' => 'summary-inline-card show-summary-item']) }}>
    <div class="summary-inline-label">{{ $label }}</div>

    <div class="summary-inline-value">
        {{ $slot }}
    </div>

    @if (filled($help))
        <div class="summary-inline-help">{{ $help }}</div>
    @endif
</div>
