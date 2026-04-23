{{-- FILE: resources/views/components/analytics/panel.blade.php | V3 --}}

@props([
    'title',
    'subtitle' => null,
    'detailsId' => null,
    'toggleLabel' => 'Ver más',
    'toggleLabelExpanded' => 'Ocultar',
])

@php
    $hasSummary = isset($summary) && trim((string) $summary) !== '';
    $hasDetails = isset($details) && trim((string) $details) !== '';
    $resolvedDetailsId = $detailsId ?: 'analytics-panel-details-' . uniqid();
@endphp

<x-card {{ $attributes->class(['analytics-panel']) }}>
    <div class="analytics-panel__header">
        <h2 class="analytics-panel__title">{{ $title }}</h2>

        @if (filled($subtitle))
            <p class="analytics-panel__subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($hasSummary)
        <div class="analytics-panel__summary">
            <div class="analytics-grid analytics-grid--summary">
                {{ $summary }}
            </div>
        </div>
    @endif

    @if ($hasDetails)
        <div class="analytics-panel__actions">
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

        <div id="{{ $resolvedDetailsId }}" class="analytics-panel__details" hidden>
            {{ $details }}
        </div>
    @endif
</x-card>