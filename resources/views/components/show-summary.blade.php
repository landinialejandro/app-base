{{-- FILE: resources/views/components/show-summary.blade.php | V4 --}}

@props([
    'detailsId' => null,
    'toggleLabel' => 'Más información',
    'toggleLabelExpanded' => 'Menos información',
    'detailsLayout' => 'grid',
])

@php
    $hasDetails = isset($details) && trim((string) $details) !== '';
    $resolvedDetailsId = $detailsId ?: 'show-summary-details-' . uniqid();
@endphp

<x-card {{ $attributes->class(['show-summary']) }}>
    <div class="show-summary-grid">
        {{ $slot }}
    </div>

    @if ($hasDetails)
        <div class="show-summary-actions">
            <x-button-secondary
                type="button"
                data-action="app-toggle-details"
                data-toggle-target="#{{ $resolvedDetailsId }}"
                data-toggle-text-collapsed="{{ $toggleLabel }}"
                data-toggle-text-expanded="{{ $toggleLabelExpanded }}"
            >
                {{ $toggleLabel }}
            </x-button-secondary>
        </div>

        <div id="{{ $resolvedDetailsId }}" class="show-summary-details" hidden>
            @if ($detailsLayout === 'grid')
                <div class="detail-grid detail-grid--3">
                    {{ $details }}
                </div>
            @else
                {{ $details }}
            @endif
        </div>
    @endif
</x-card>