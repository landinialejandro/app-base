{{-- FILE: resources/views/components/tab-toolbar.blade.php | V2 --}}

@props([
    'label' => null,
])

<div {{ $attributes->class(['card', 'tab-toolbar-card']) }}>
    <div class="card-header tab-toolbar">
        <div class="tab-toolbar__nav">
            @if ($label)
                <span class="tab-toolbar__label">{{ $label }}</span>
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
