{{-- FILE: resources/views/orders/components/linked-order-action.blade.php | V1 --}}

@props([
    'action' => [],
    'variant' => 'inline', // button | summary | inline
])

@php
    $supported = (bool) ($action['supported'] ?? false);
    $linked = (bool) ($action['linked'] ?? false);
    $canView = (bool) ($action['can_view'] ?? false);
    $canCreate = (bool) ($action['can_create'] ?? false);
    $showUrl = $action['show_url'] ?? null;
    $createUrl = $action['create_url'] ?? null;
    $label = $action['label'] ?? 'Orden';
    $contactLabel = $action['contact_label'] ?? 'Contacto';
    $hasRequiredParty = (bool) ($action['has_required_party'] ?? false);
    $linkedText = $action['linked_text'] ?? $label;

    if (!$supported) {
        $state = 'hidden';
    } elseif ($linked) {
        $state = $canView && $showUrl ? 'linked_viewable' : 'linked_readonly';
    } elseif ($hasRequiredParty && $canCreate && $createUrl) {
        $state = 'creatable';
    } elseif (!$hasRequiredParty) {
        $state = 'missing_requirement';
    } else {
        $state = 'hidden';
    }

    $createText = 'Crear ' . strtolower($label);
    $viewText = 'Ver ' . strtolower($label);
    $missingText = 'Asociá un ' . strtolower($contactLabel) . ' para poder crear una ' . strtolower($label) . '.';
@endphp

@if ($state !== 'hidden')
    @if ($variant === 'button')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}" class="btn btn-secondary">
                {{ $viewText }}
            </a>
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}" class="btn btn-secondary">
                {{ $createText }}
            </a>
        @elseif ($state === 'missing_requirement')
            <span class="btn btn-secondary disabled" aria-disabled="true" title="{{ $missingText }}">
                {{ $createText }}
            </span>
        @endif
    @elseif ($variant === 'summary')
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $linkedText }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $linkedText }}
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $createText }}</a>
        @elseif ($state === 'missing_requirement')
            {{ $missingText }}
        @endif
    @else
        @if ($state === 'linked_viewable')
            <a href="{{ $showUrl }}">{{ $linkedText }}</a>
        @elseif ($state === 'linked_readonly')
            {{ $linkedText }}
        @elseif ($state === 'creatable')
            <a href="{{ $createUrl }}">{{ $createText }}</a>
        @elseif ($state === 'missing_requirement')
            <span class="text-muted">{{ $missingText }}</span>
        @endif
    @endif
@endif
