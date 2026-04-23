{{-- FILE: resources/views/inventory/components/order-item-row-actions.blade.php | V8 --}}

@props([
    'actions' => [],
])

@php
    $actions = collect($actions ?? [])->values();

    $resolveVariant = function (string $actionKey, string $type) {
        return match ($actionKey) {
            'execute' => 'primary',
            'return' => 'danger',
            'view_movements' => 'secondary',
            default => $type === 'modal' ? 'primary' : 'secondary',
        };
    };
@endphp

@foreach ($actions as $action)
    @php
        $type = $action['type'] ?? 'button';
        $actionKey = (string) ($action['action_key'] ?? '');
        $title = $action['title'] ?? ($action['label'] ?? 'Acción');
        $label = $action['label'] ?? $title;
        $icon = $action['icon'] ?? null;
        $modalId = $action['modal_id'] ?? null;
        $href = $action['href'] ?? null;
        $variant = $resolveVariant($actionKey, $type);
    @endphp

    @if ($type === 'modal' && $modalId)
        <x-button-tool-button
            :title="$title"
            :label="$label"
            :variant="$variant"
            data-action="app-modal-open"
            data-modal-target="#{{ $modalId }}"
        >
            @switch($icon)
                @case('truck')
                    <x-icons.truck />
                @break

                @case('plus')
                    <x-icons.plus />
                @break

                @case('rotate-ccw')
                    <x-icons.rotate-ccw />
                @break

                @case('check')
                    <x-icons.check />
                @break

                @case('eye')
                    <x-icons.eye />
                @break

                @default
                    <x-icons.check />
            @endswitch
        </x-button-tool-button>

        @include($action['modal_view'], [
            'order' => $action['order'] ?? null,
            'row' => $action['row'] ?? [],
            'trailQuery' => $action['trailQuery'] ?? [],
            'modalId' => $modalId,
        ])
    @elseif ($type === 'link' && $href)
        <x-button-tool
            :href="$href"
            :title="$title"
            :label="$label"
            :variant="$variant"
        >
            @switch($icon)
                @case('truck')
                    <x-icons.truck />
                @break

                @case('plus')
                    <x-icons.plus />
                @break

                @case('rotate-ccw')
                    <x-icons.rotate-ccw />
                @break

                @case('check')
                    <x-icons.check />
                @break

                @case('eye')
                    <x-icons.eye />
                @break

                @default
                    <x-icons.eye />
            @endswitch
        </x-button-tool>
    @endif
@endforeach