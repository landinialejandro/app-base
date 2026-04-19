{{-- FILE: resources/views/projects/components/linked-project-action.blade.php | V1 --}}

@props([
    'action' => [],
    'variant' => 'inline',
])

@php
    $supported = (bool) ($action['supported'] ?? false);
    $linked = (bool) ($action['linked'] ?? false);
    $canView = (bool) ($action['can_view'] ?? false);
    $hidden = (bool) ($action['hidden'] ?? false);

    $showUrl = $action['show_url'] ?? null;
    $label = $action['label'] ?? 'Proyecto';
    $linkedText = $action['linked_text'] ?? $label;

    if (!$supported || $hidden) {
        $state = 'hidden';
    } elseif ($linked) {
        $state = $canView && $showUrl ? 'linked_viewable' : 'linked_readonly';
    } else {
        $state = 'hidden';
    }
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'button')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}" class="btn btn-secondary">
                Ver proyecto
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
