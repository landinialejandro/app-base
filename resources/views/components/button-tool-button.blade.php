{{-- FILE: resources/views/components/button-tool-button.blade.php | V2 --}}

@props([
    'title',
    'label' => null,
    'variant' => 'secondary',
    'type' => 'button',
])

@php
    $classes = \App\Support\Ui\ButtonToolStyle::classes($variant);
@endphp

<button
    type="{{ $type }}"
    title="{{ $title }}"
    aria-label="{{ $label ?? $title }}"
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</button>