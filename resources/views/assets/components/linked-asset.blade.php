{{-- FILE: resources/views/assets/components/linked-asset.blade.php | V1 --}}

@props([
    'linked' => [],
    'variant' => 'inline', // inline | summary
])

@php
    $state = $linked['state'] ?? 'hidden';
    $showUrl = $linked['show_url'] ?? null;
    $label = $linked['label'] ?? 'Activo';
    $text = $linked['text'] ?? $label;
@endphp

@if ($state !== 'hidden')
    @if ($state === 'linked_viewable')
        <a href="{{ $showUrl }}">{{ $text }}</a>
    @elseif ($state === 'linked_readonly')
        <span>{{ $text }}</span>
    @else
        <span>—</span>
    @endif
@endif
