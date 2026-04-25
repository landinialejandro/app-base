{{-- FILE: resources/views/inventory/components/row-actions.blade.php | V2 --}}

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
            default => in_array($type, ['modal', 'submit'], true) ? 'primary' : 'secondary',
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
        <x-button-tool-button :title="$title" :label="$label" :variant="$variant" data-action="app-modal-open"
            data-modal-target="#{{ $modalId }}">
            @include('inventory.components.row-action-icon', ['icon' => $icon])
        </x-button-tool-button>

        @include($action['modal_view'], [
            'action' => $action['action'] ?? '#',
            'method' => $action['method'] ?? 'POST',
            'hiddenFields' => $action['hiddenFields'] ?? [],
            'title' => $title,
            'order' => $action['order'] ?? null,
            'document' => $action['document'] ?? null,
            'row' => $action['row'] ?? [],
            'trailQuery' => $action['trailQuery'] ?? [],
            'modalId' => $modalId,
        ])
    @elseif ($type === 'submit' && !empty($action['action']))
        <x-button-tool-submit :action="$action['action']" method="POST" :variant="$variant" :title="$title" :label="$label"
            :message="$action['message'] ?? '¿Deseas continuar?'">
            @include('inventory.components.row-action-icon', ['icon' => $icon])

            @foreach ($action['fields'] ?? [] as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
        </x-button-tool-submit>
    @elseif ($type === 'link' && $href)
        <x-button-tool :href="$href" :title="$title" :label="$label" :variant="$variant">
            @include('inventory.components.row-action-icon', ['icon' => $icon])
        </x-button-tool>
    @endif
@endforeach
