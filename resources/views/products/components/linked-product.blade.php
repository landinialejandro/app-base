{{-- FILE: resources/views/products/components/linked-product.blade.php | V4 --}}

@props([
    'linked' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $state = $linked['state'] ?? 'hidden';
    $showUrl = $linked['show_url'] ?? null;
    $createUrl = $linked['create_url'] ?? null;
    $label = $linked['label'] ?? 'Artículo';
    $text = $linked['text'] ?? $label;

    $createText = 'Crear ' . strtolower($label);
    $viewText = 'Ver ' . strtolower($label);
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'button')
        @if ($state === 'linked_viewable')
            <x-button-secondary :href="$showUrl">
                {{ $viewText }}
            </x-button-secondary>
        @elseif ($state === 'creatable')
            <x-button-secondary :href="$createUrl">
                {{ $createText }}
            </x-button-secondary>
        @elseif ($state === 'linked_readonly')
            <span class="btn btn-secondary disabled" aria-disabled="true">
                {{ $text }}
            </span>
        @else
            <span class="btn btn-secondary disabled" aria-disabled="true">
                —
            </span>
        @endif
    @else
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $text }}</a>
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $text }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $text }}
        @else
            —
        @endif
    @endif
@endif