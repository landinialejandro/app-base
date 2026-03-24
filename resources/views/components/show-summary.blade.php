{{-- FILE: resources/views/components/show-summary.blade.php --}}

@props([
    'detailsId' => null,
    'toggleLabel' => 'Más información',
    'toggleLabelExpanded' => 'Menos información',
])

@php
    $hasDetails = isset($details) && trim((string) $details) !== '';
    $resolvedDetailsId = $detailsId ?: 'show-summary-details-' . uniqid();
@endphp

<x-card {{ $attributes->merge(['class' => 'show-summary']) }}>
    <div class="show-summary-grid">
        {{ $slot }}
    </div>

    @if ($hasDetails)
        <div class="show-summary-actions">
            <button type="button" class="btn btn-secondary" data-action="app-toggle-details"
                data-toggle-target="#{{ $resolvedDetailsId }}" data-toggle-text-collapsed="{{ $toggleLabel }}"
                data-toggle-text-expanded="{{ $toggleLabelExpanded }}">
                {{ $toggleLabel }}
            </button>
        </div>

        <div id="{{ $resolvedDetailsId }}" class="show-summary-details" hidden>
            <div class="detail-grid detail-grid--3">
                {{ $details }}
            </div>
        </div>
    @endif
</x-card>
