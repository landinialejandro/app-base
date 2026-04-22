{{-- FILE: resources/views/inventory/components/order-item-row-actions.blade.php | V1 --}}

@props([
    'actions' => [],
])

@php
    $actions = collect($actions ?? [])->values();
@endphp

@foreach ($actions as $action)
    @php
        $type = $action['type'] ?? 'button';
        $title = $action['title'] ?? ($action['label'] ?? 'Acción');
        $buttonClass = $action['button_class'] ?? 'btn btn-secondary btn-icon';
        $icon = $action['icon'] ?? null;
        $modalId = $action['modal_id'] ?? null;
    @endphp

    @if ($type === 'modal' && $modalId)
        <button type="button" class="{{ $buttonClass }}" data-action="app-modal-open"
            data-modal-target="#{{ $modalId }}" title="{{ $title }}" aria-label="{{ $title }}">
            @if ($icon === 'truck')
                <x-icons.truck />
            @endif
        </button>

        @include($action['modal_view'], [
            'order' => $action['order'] ?? null,
            'row' => $action['row'] ?? [],
            'trailQuery' => $action['trailQuery'] ?? [],
            'modalId' => $modalId,
        ])
    @endif
@endforeach