{{-- FILE: resources/views/components/card.blade.php | V4 --}}

@props([
    'collapsible' => false,
    'collapsed' => false,
    'bodyClass' => null,
])

@php
    $hasHeader =
        (isset($header) && trim((string) $header) !== '') || (isset($toolbox) && trim((string) $toolbox) !== '');
    $cardBodyId = 'card-body-' . uniqid();
@endphp

<div {{ $attributes->class(['card', 'card--collapsible' => $collapsible]) }}
    @if ($collapsible) data-card data-card-collapsible @endif>
    @if ($hasHeader)
        <div class="card-header">
            <div class="card-header__main">
                {{ $header ?? '' }}
            </div>

            @if (isset($toolbox) && trim((string) $toolbox) !== '')
                <div class="card-toolbox">
                    {{ $toolbox }}
                </div>
            @endif
        </div>
    @endif

    <div id="{{ $cardBodyId }}" class="{{ trim('card-body ' . ($bodyClass ?? '')) }}"
        @if ($collapsible) data-card-body @endif @if ($collapsible && $collapsed) hidden @endif>
        {{ $slot }}
    </div>
</div>
