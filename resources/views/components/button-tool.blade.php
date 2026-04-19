{{-- FILE:resources/views/components/button-tool.blade.php | V1 --}}
@props(['href', 'title', 'label' => null, 'variant' => 'secondary'])

@php
    $classes = match ($variant) {
        'danger' => 'btn btn-danger btn-icon btn-tool',
        'primary' => 'btn btn-primary btn-icon btn-tool',
        default => 'btn btn-secondary btn-icon btn-tool',
    };
@endphp

<a href="{{ $href }}" title="{{ $title }}" aria-label="{{ $label ?? $title }}"
    {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
