{{-- FILE: resources/views/components/tab-toolbar.blade.php | V3 --}}

@props([
    'label' => 'Secciones',
    'context' => null,
])

@php
    $hasContext = is_string($context ?? null) && trim($context) !== '';
@endphp

<div {{ $attributes->class(['card', 'tab-toolbar-card']) }}>
    <div class="card-header tab-toolbar">
        <div class="tab-toolbar__nav">
            @if ($label)
                <span class="tab-toolbar__label">
                    {{ $label }}

                    @if ($hasContext)
                        <x-help-tooltip :text="$context" :title="$label" />
                    @endif
                </span>
            @endif

            {{ $tabs ?? '' }}
        </div>

        @if (isset($actions) && trim((string) $actions) !== '')
            <div class="tab-toolbar__actions">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>