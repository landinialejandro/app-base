{{-- FILE: resources/views/inventory/components/linked-inventory.blade.php | V2 --}}

@props([
    'linked' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $state = $linked['state'] ?? 'hidden';
    $showUrl = $linked['show_url'] ?? null;
    $label = $linked['label'] ?? 'Inventario';
    $text = $linked['text'] ?? $label;

    $viewText = 'Ver ' . strtolower($label);
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'button')
        @if ($state === 'linked_viewable')
            <x-button-secondary :href="$showUrl">
                {{ $viewText }}
            </x-button-secondary>
        @elseif ($state === 'linked_readonly')
            <span class="btn btn-secondary disabled" aria-disabled="true">
                {{ $text }}
            </span>
        @endif
    @else
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $text }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $text }}
        @endif
    @endif
@endif