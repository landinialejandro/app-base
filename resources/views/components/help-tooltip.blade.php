{{-- FILE: resources/views/components/help-tooltip.blade.php | V1 --}}

@props([
    'text',
    'title' => 'Ayuda',
])

<span {{ $attributes->class(['help-tooltip']) }}>
    <button
        type="button"
        class="help-tooltip__trigger"
        title="{{ $title }}"
        aria-label="{{ $title }}"
    >
        <x-icons.info />
    </button>

    <span class="help-tooltip__content" role="tooltip">
        {{ $text }}
    </span>
</span>