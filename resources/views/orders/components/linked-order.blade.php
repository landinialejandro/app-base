{{-- FILE: resources/views/orders/components/linked-order.blade.php | V4 --}}

@props([
    'linked' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $state = $linked['state'] ?? 'hidden';
    $showUrl = $linked['show_url'] ?? null;
    $createUrl = $linked['create_url'] ?? null;
    $label = $linked['label'] ?? 'Orden';
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
        @endif
    @elseif ($variant === 'summary')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $text }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $text }}
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $createText }}</a>
        @endif
    @else
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $text }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $text }}
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $createText }}</a>
        @endif
    @endif
@endif

<x-dev-component-version name="orders.components.linked-order" version="V4" align="right" />