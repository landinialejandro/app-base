{{-- FILE: resources/views/tasks/components/linked-task.blade.php | V1 --}}

@props([
    'linked' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $state = $linked['state'] ?? 'hidden';
    $showUrl = $linked['show_url'] ?? null;
    $label = $linked['label'] ?? 'Tarea';
    $text = $linked['text'] ?? $label;
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'button')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}" class="btn btn-secondary">
                Ver tarea
            </a>
        @elseif ($state === 'linked_readonly')
            <span class="btn btn-secondary disabled" aria-disabled="true">
                {{ $text }}
            </span>
        @else
            <span class="btn btn-secondary disabled" aria-disabled="true">
                —
            </span>
        @endif
    @else
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $text }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $text }}
        @else
            —
        @endif
    @endif
@endif
