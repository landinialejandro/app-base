{{-- FILE: resources/views/inventory/components/movement-action.blade.php | V2 --}}

@props([
    'action' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $state = $action['state'] ?? 'hidden';
    $createUrl = $action['create_url'] ?? null;
    $label = $action['label'] ?? 'Movimiento';
    $text = $action['text'] ?? 'Agregar ' . strtolower($label);
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'button')
        @if ($state === 'creatable')
            <x-button-secondary :href="$createUrl">
                {{ $text }}
            </x-button-secondary>
        @else
            <span class="btn btn-secondary disabled" aria-disabled="true">
                {{ $text }}
            </span>
        @endif
    @elseif ($variant === 'summary')
        @if ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $text }}</a>
        @else
            {{ $text }}
        @endif
    @else
        @if ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $text }}</a>
        @else
            {{ $text }}
        @endif
    @endif
@endif