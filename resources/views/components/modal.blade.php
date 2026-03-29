{{-- FILE: resources/views/components/modal.blade.php | V2 --}}

@props(['id', 'title' => null, 'size' => 'md'])

@php
    $sizeClass = match ($size) {
        'sm' => 'app-modal__dialog--sm',
        'lg' => 'app-modal__dialog--lg',
        'xl' => 'app-modal__dialog--xl',
        default => 'app-modal__dialog--md',
    };
@endphp

<div {{ $attributes->class(['app-modal']) }} id="{{ $id }}" hidden aria-hidden="true" data-modal-root>
    <div class="app-modal__backdrop" data-action="app-modal-close" data-modal-target="#{{ $id }}"></div>

    <div class="app-modal__dialog {{ $sizeClass }}" role="dialog" aria-modal="true"
        @if ($title) aria-label="{{ $title }}" @endif>
        <div class="app-modal__header">
            @if ($title)
                <h3 class="app-modal__title">{{ $title }}</h3>
            @else
                <div></div>
            @endif

            <button type="button" class="btn btn-secondary btn-icon" data-action="app-modal-close"
                data-modal-target="#{{ $id }}" aria-label="Cerrar ventana" title="Cerrar ventana">
                <x-icons.x />
            </button>
        </div>

        <div class="app-modal__body">
            {{ $slot }}
        </div>

        @if (isset($footer) && trim((string) $footer) !== '')
            <div class="app-modal__footer">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
