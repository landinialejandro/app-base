{{-- FILE: resources/views/inventory/components/linked-movement-action.blade.php | V1 --}}

@props([
    'action' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $supported = (bool) ($action['supported'] ?? false);
    $linked = (bool) ($action['linked'] ?? false);
    $canView = (bool) ($action['can_view'] ?? false);
    $canCreate = (bool) ($action['can_create'] ?? false);
    $readonly = (bool) ($action['readonly'] ?? false);
    $hidden = (bool) ($action['hidden'] ?? false);

    $showUrl = $action['show_url'] ?? null;
    $createUrl = $action['create_url'] ?? null;
    $label = $action['label'] ?? 'Movimiento';
    $linkedText = $action['linked_text'] ?? $label;

    if (!$supported || $hidden) {
        $state = 'hidden';
    } elseif ($linked) {
        $state = $canView && $showUrl ? 'linked_viewable' : 'linked_readonly';
    } elseif ($canCreate && $createUrl) {
        $state = 'creatable';
    } else {
        $state = 'hidden';
    }

    $createText = 'Agregar ' . strtolower($label);
    $viewText = 'Ver ' . strtolower($label);
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'button')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}" class="btn btn-secondary">
                {{ $viewText }}
            </a>
        @elseif ($state === 'linked_readonly')
            <span class="btn btn-secondary disabled" aria-disabled="true">
                {{ $linkedText }}
            </span>
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}" class="btn btn-secondary">
                {{ $createText }}
            </a>
        @endif
    @elseif ($variant === 'summary')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $linkedText }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $linkedText }}
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $createText }}</a>
        @endif
    @else
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $linkedText }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $linkedText }}
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $createText }}</a>
        @endif
    @endif
@endif
