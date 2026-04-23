{{-- FILE: resources/views/components/button-success.blade.php | V1 --}}

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
        {{ $attributes->merge(['class' => 'btn btn-success']) }}
    >
        {{ $slot }}
    </button>
@else
    <a
        href="{{ $href }}"
        @if ($title) title="{{ $title }}" @endif
        @if ($label ?? $title) aria-label="{{ $label ?? $title }}" @endif
        {{ $attributes->merge(['class' => 'btn btn-success']) }}
    >
        {{ $slot }}
    </a>
@endif