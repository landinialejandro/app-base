{{-- FILE: resources/views/orders/partials/status-quick-actions.blade.php | V4 --}}

@php
    use App\Support\Catalogs\OrderCatalog;

    $order = $order ?? null;
    $trailQuery = $trailQuery ?? [];

    if (!$order) {
        return;
    }

    $actions = [];

    if (
        $order->status !== OrderCatalog::STATUS_APPROVED &&
        OrderCatalog::canTransition($order->status, OrderCatalog::STATUS_APPROVED)
    ) {
        $actions[] = [
            'status' => OrderCatalog::STATUS_APPROVED,
            'title' => 'Aprobar orden',
            'aria' => 'Aprobar orden',
            'class' => 'btn btn-warning btn-icon',
            'message' => '¿Cambiar el estado de la orden a Aprobada?',
            'icon' => 'check',
        ];
    }

    if (
        $order->status !== OrderCatalog::STATUS_CLOSED &&
        OrderCatalog::canTransition($order->status, OrderCatalog::STATUS_CLOSED)
    ) {
        $actions[] = [
            'status' => OrderCatalog::STATUS_CLOSED,
            'title' => 'Cerrar orden',
            'aria' => 'Cerrar orden',
            'class' => 'btn btn-success btn-icon',
            'message' => '¿Cambiar el estado de la orden a Cerrada?',
            'icon' => 'circle-check',
        ];
    }

    if (
        $order->status !== OrderCatalog::STATUS_CANCELLED &&
        OrderCatalog::canTransition($order->status, OrderCatalog::STATUS_CANCELLED)
    ) {
        $actions[] = [
            'status' => OrderCatalog::STATUS_CANCELLED,
            'title' => 'Cancelar orden',
            'aria' => 'Cancelar orden',
            'class' => 'btn btn-danger btn-icon',
            'message' => '¿Cambiar el estado de la orden a Cancelada?',
            'icon' => 'x',
        ];
    }
@endphp

@if (!empty($actions))
    <div class="compact-actions compact-actions--end u-w-full u-mt-2">
        @foreach ($actions as $action)
            <form method="POST" action="{{ route('orders.status.update', ['order' => $order] + $trailQuery) }}"
                class="inline-form" data-action="app-confirm-submit" data-confirm-message="{{ $action['message'] }}">
                @csrf

                <input type="hidden" name="status" value="{{ $action['status'] }}">

                <button type="submit" class="{{ $action['class'] }}" title="{{ $action['title'] }}"
                    aria-label="{{ $action['aria'] }}">
                    @if ($action['icon'] === 'check')
                        <x-icons.check />
                    @elseif ($action['icon'] === 'circle-check')
                        <x-icons.circle-check />
                    @elseif ($action['icon'] === 'x')
                        <x-icons.x />
                    @endif
                </button>
            </form>
        @endforeach
    </div>
@endif
