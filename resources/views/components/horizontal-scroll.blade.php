{{-- FILE: resources/views/components/horizontal-scroll.blade.php | V1 --}}

@props([
    'label' => null,
])

<div class="horizontal-scroll" data-horizontal-scroll>
    <button type="button" class="horizontal-scroll-button horizontal-scroll-button--left" data-horizontal-scroll-prev
        aria-label="Desplazar a la izquierda" hidden>
        <x-icons.chevron-left />
    </button>

    <div class="horizontal-scroll-viewport" data-horizontal-scroll-viewport
        @if ($label) aria-label="{{ $label }}" @endif>
        <div class="horizontal-scroll-track">
            {{ $slot }}
        </div>
    </div>

    <button type="button" class="horizontal-scroll-button horizontal-scroll-button--right" data-horizontal-scroll-next
        aria-label="Desplazar a la derecha" hidden>
        <x-icons.chevron-right />
    </button>
</div>
