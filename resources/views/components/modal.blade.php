{{-- FILE: resources/views/components/modal.blade.php | V5 --}}

@props(['id', 'title' => null, 'size' => 'md'])

@php
    $sizeClass = match ($size) {
        'sm' => 'app-modal__dialog--sm',
        'lg' => 'app-modal__dialog--lg',
        'xl' => 'app-modal__dialog--xl',
        default => 'app-modal__dialog--md',
    };

    $hasHeaderActions = isset($headerActions) && trim((string) $headerActions) !== '';
@endphp

<div {{ $attributes->class(['app-modal']) }} id="{{ $id }}" hidden aria-hidden="true" data-modal-root>
    <div class="app-modal__backdrop" data-action="app-modal-close" data-modal-target="#{{ $id }}"></div>

    <div class="app-modal__dialog {{ $sizeClass }}" role="dialog" aria-modal="true"
        @if ($title) aria-label="{{ $title }}" @endif>
        <x-card class="app-modal__card" body-class="app-modal__body">
            <x-slot:header>
                @if ($title)
                    <h3 class="card-title app-modal__title">{{ $title }}</h3>
                @else
                    <span></span>
                @endif
            </x-slot:header>

            <x-slot:toolbox>
                @if ($hasHeaderActions)
                    {{ $headerActions }}
                @endif

                <x-button-secondary
                    type="button"
                    class="btn-icon"
                    data-action="app-modal-close"
                    data-modal-target="#{{ $id }}"
                    title="Cerrar ventana"
                    aria-label="Cerrar ventana"
                >
                    <x-icons.x />
                </x-button-secondary>
            </x-slot:toolbox>

            {{ $slot }}

            @if (isset($footer) && trim((string) $footer) !== '')
                <div class="app-modal__footer">
                    {{ $footer }}
                </div>
            @endif
        </x-card>
    </div>
</div>