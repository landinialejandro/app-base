{{-- FILE: resources/views/projects/components/linked-project.blade.php | V3 --}}

@props([
    'linked' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $state = $linked['state'] ?? 'hidden';
    $showUrl = $linked['show_url'] ?? null;
    $createUrl = $linked['create_url'] ?? null;
    $label = $linked['label'] ?? 'Proyecto';
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