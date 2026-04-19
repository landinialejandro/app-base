{{-- FILE: resources/views/tasks/components/linked-task-action.blade.php | V1 --}}

@php
    $action = $action ?? [
        'supported' => false,
        'linked' => false,
        'can_view' => false,
        'hidden' => true,
        'label' => 'Tarea',
        'text' => '—',
        'show_url' => null,
    ];
@endphp

@if (!($action['hidden'] ?? false))
    @if (($action['can_view'] ?? false) && !empty($action['show_url']))
        <a href="{{ $action['show_url'] }}">
            {{ $action['text'] ?? '—' }}
        </a>
    @else
        {{ $action['text'] ?? '—' }}
    @endif
@endif
