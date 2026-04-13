{{-- FILE: resources/views/parties/components/linked-party-action.blade.php | V1 --}}

@props([
    'action' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $supported = (bool) ($action['supported'] ?? false);
    $linked = (bool) ($action['linked'] ?? false);
    $canView = (bool) ($action['can_view'] ?? false);
    $showUrl = $action['show_url'] ?? null;
    $label = $action['label'] ?? 'Contacto';
    $linkedText = $action['linked_text'] ?? $label;

    if (!$supported || !$linked) {
        $state = 'hidden';
    } elseif ($canView && $showUrl) {
        $state = 'linked_viewable';
    } else {
        $state = 'linked_readonly';
    }

    $viewText = 'Ver ' . strtolower($label);
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'button')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}" class="btn btn-secondary">
                {{ $viewText }}
            </a>
        @else
            <span class="btn btn-secondary disabled" aria-disabled="true">
                {{ $linkedText }}
            </span>
        @endif
    @elseif ($variant === 'summary')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $linkedText }}</a>
        @else
            {{ $linkedText }}
        @endif
    @else
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $linkedText }}</a>
        @else
            {{ $linkedText }}
        @endif
    @endif
@endif
