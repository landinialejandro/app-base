{{-- FILE: resources/views/orders/components/linked-order.blade.php | V1 --}}

@props([
    'linked' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $state = $linked['state'] ?? 'hidden';
    $showUrl = $linked['show_url'] ?? null;
    $createUrl = $linked['create_url'] ?? null;
    $label = $linked['label'] ?? 'Orden';
    $contactLabel = $linked['contact_label'] ?? 'Contacto';
    $text = $linked['text'] ?? $label;

    $createText = 'Crear ' . strtolower($label);
    $viewText = 'Ver ' . strtolower($label);
    $missingText = 'Asociá un ' . strtolower($contactLabel) . ' para poder crear una ' . strtolower($label) . '.';
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'button')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}" class="btn btn-secondary">{{ $viewText }}</a>
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}" class="btn btn-secondary">{{ $createText }}</a>
        @elseif ($state === 'missing_requirement')
            <span class="btn btn-secondary disabled" aria-disabled="true" title="{{ $missingText }}">
                {{ $createText }}
            </span>
        @endif
    @elseif ($variant === 'summary')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $text }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $text }}
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $createText }}</a>
        @elseif ($state === 'missing_requirement')
            {{ $missingText }}
        @endif
    @else
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $text }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $text }}
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $createText }}</a>
        @elseif ($state === 'missing_requirement')
            <span class="text-muted">{{ $missingText }}</span>
        @endif
    @endif
@endif
