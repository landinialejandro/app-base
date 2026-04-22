{{-- FILE: resources/views/inventory/components/movement-action.blade.php | V1 --}}

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
            <a href="{{ $createUrl }}" class="btn btn-secondary">
                {{ $text }}
            </a>
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
