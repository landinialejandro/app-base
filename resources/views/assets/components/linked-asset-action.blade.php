{{-- FILE: resources/views/assets/components/linked-asset-action.blade.php | V1 --}}

@props([
    'action' => [],
    'variant' => 'inline', // inline | summary
])

@php
    $supported = (bool) ($action['supported'] ?? false);
    $linked = (bool) ($action['linked'] ?? false);
    $canView = (bool) ($action['can_view'] ?? false);
    $showUrl = $action['show_url'] ?? null;
    $label = $action['label'] ?? 'Activo';
    $linkedText = $action['linked_text'] ?? $label;

    if (!$supported) {
        $state = 'hidden';
    } elseif (!$linked) {
        $state = 'missing';
    } elseif ($canView && $showUrl) {
        $state = 'linked_viewable';
    } else {
        $state = 'linked_readonly';
    }
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'summary')
        @switch($state)
            @case('linked_viewable')
                <a href="{{ $showUrl }}">{{ $linkedText }}</a>
            @break

            @case('linked_readonly')
                <span>{{ $linkedText }}</span>
            @break

            @default
                <span>—</span>
        @endswitch
    @else
        @switch($state)
            @case('linked_viewable')
                <a href="{{ $showUrl }}">{{ $linkedText }}</a>
            @break

            @case('linked_readonly')
                <span>{{ $linkedText }}</span>
            @break

            @default
                <span>—</span>
        @endswitch
    @endif
@endif
