{{-- FILE: resources/views/components/status-transition-actions.blade.php | V1 --}}

@props([
    'record',
    'catalog',
    'routeName',
    'routeParam',
    'trailQuery' => [],
    'resourceLabel' => 'registro',
    'approvedLabel' => 'Aprobado',
    'closedLabel' => 'Cerrado',
    'cancelledLabel' => 'Cancelado',
])

@php
    if (!$record || !method_exists($catalog, 'canTransition')) {
        return;
    }

    $status = $record->status;
    $actions = [];

    if (
        defined($catalog.'::STATUS_APPROVED')
        && $status !== $catalog::STATUS_APPROVED
        && $catalog::canTransition($status, $catalog::STATUS_APPROVED)
    ) {
        $isReopen = defined($catalog.'::STATUS_CLOSED') && $status === $catalog::STATUS_CLOSED;

        $actions[] = [
            'status' => $catalog::STATUS_APPROVED,
            'title' => $isReopen ? 'Reabrir '.$resourceLabel : 'Aprobar '.$resourceLabel,
            'message' => $isReopen
                ? '¿Reabrir '.$resourceLabel.' y volverlo al estado '.$approvedLabel.'?'
                : '¿Cambiar el estado de '.$resourceLabel.' a '.$approvedLabel.'?',
            'variant' => 'warning',
            'icon' => 'check',
        ];
    }

    if (
        defined($catalog.'::STATUS_CLOSED')
        && $status !== $catalog::STATUS_CLOSED
        && $catalog::canTransition($status, $catalog::STATUS_CLOSED)
    ) {
        $actions[] = [
            'status' => $catalog::STATUS_CLOSED,
            'title' => 'Cerrar '.$resourceLabel,
            'message' => '¿Cambiar el estado de '.$resourceLabel.' a '.$closedLabel.'?',
            'variant' => 'success',
            'icon' => 'circle-check',
        ];
    }

    if (
        defined($catalog.'::STATUS_CANCELLED')
        && $status !== $catalog::STATUS_CANCELLED
        && $catalog::canTransition($status, $catalog::STATUS_CANCELLED)
    ) {
        $actions[] = [
            'status' => $catalog::STATUS_CANCELLED,
            'title' => 'Cancelar '.$resourceLabel,
            'message' => '¿Cambiar el estado de '.$resourceLabel.' a '.$cancelledLabel.'?',
            'variant' => 'danger',
            'icon' => 'x',
        ];
    }
@endphp

@if (!empty($actions))
    <div class="compact-actions compact-actions--end u-w-full u-mt-2">
        @foreach ($actions as $action)
            <x-button-tool-submit
                :action="route($routeName, [$routeParam => $record] + $trailQuery)"
                :title="$action['title']"
                :label="$action['title']"
                :message="$action['message']"
                :variant="$action['variant']"
            >
                <x-slot:fields>
                    <input type="hidden" name="status" value="{{ $action['status'] }}">
                </x-slot:fields>

                @if ($action['icon'] === 'check')
                    <x-icons.check />
                @elseif ($action['icon'] === 'circle-check')
                    <x-icons.circle-check />
                @elseif ($action['icon'] === 'x')
                    <x-icons.x />
                @endif
            </x-button-tool-submit>
        @endforeach
    </div>
@endif