{{-- FILE: resources/views/components/button-tool.blade.php | V2 --}}

@props(['href', 'title', 'label' => null, 'variant' => 'secondary'])

@php
    $classes = \App\Support\Ui\ButtonToolStyle::classes($variant);
@endphp

<a href="{{ $href }}" title="{{ $title }}" aria-label="{{ $label ?? $title }}"
    {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>