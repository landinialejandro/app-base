{{-- FILE: resources/views/components/button-primary.blade.php | V1 --}}

@props([
    'href' => null,
    'type' => null,
    'title' => null,
    'label' => null,
])

@if ($type)
    <button
        type="{{ $type }}"
        @if ($title) title="{{ $title }}" @endif
        @if ($label ?? $title) aria-label="{{ $label ?? $title }}" @endif
        {{ $attributes->merge(['class' => 'btn btn-primary']) }}
    >
        {{ $slot }}
    </button>
@else
    <a
        href="{{ $href }}"
        @if ($title) title="{{ $title }}" @endif
        @if ($label ?? $title) aria-label="{{ $label ?? $title }}" @endif
        {{ $attributes->merge(['class' => 'btn btn-primary']) }}
    >
        {{ $slot }}
    </a>
@endif