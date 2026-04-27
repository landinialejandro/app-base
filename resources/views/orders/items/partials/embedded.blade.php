{{-- FILE: resources/views/orders/items/partials/embedded.blade.php | V10 --}}

@php
    use App\Support\Catalogs\OrderCatalog;
    use App\Support\Catalogs\OrderItemCatalog;
    use App\Support\Inventory\OrderItemStatusService;
    use App\Support\LineItems\LineItemTotals;
    use App\Support\LineItems\LineItemViewHelper;

    $viewHelper = app(LineItemViewHelper::class);

    $order = $order ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en esta orden.';
    $trailQuery = $trailQuery ?? [];
    $supportsProductsModule = $supportsProductsModule ?? true;

    $executedTotal = $order
        ? app(LineItemTotals::class)->executedTotal(
            $items,
            fn ($item) => app(OrderItemStatusService::class)->executedQuantity($item),
        )
        : 0;

    $statuses = [
        OrderItemCatalog::STATUS_PENDING => OrderItemCatalog::statusLabel(OrderItemCatalog::STATUS_PENDING),
        OrderItemCatalog::STATUS_PARTIAL => OrderItemCatalog::statusLabel(OrderItemCatalog::STATUS_PARTIAL),
        OrderItemCatalog::STATUS_COMPLETED => OrderItemCatalog::statusLabel(OrderItemCatalog::STATUS_COMPLETED),
        OrderItemCatalog::STATUS_CANCELLED => OrderItemCatalog::statusLabel(OrderItemCatalog::STATUS_CANCELLED),
    ];

    $addUrl = null;

    if ($order && auth()->user()?->can('update', $order) && !OrderCatalog::isReadonlyStatus($order->status)) {
        $addUrl = route('orders.items.create', ['order' => $order] + $trailQuery);
    }

    $summaryItems = [
        [
            'label' => 'Cantidad de ítems',
            'value' => $items->count(),
        ],
        [
            'label' => 'Total estructural',
            'value' => $viewHelper->money($order?->total ?? 0),
            'subvalue' => 'Entregado: '.$viewHelper->money($executedTotal),
        ],
    ];

    $extraHelp = $supportsProductsModule
        ? null
        : 'El módulo de productos no está habilitado para esta empresa. Los ítems pueden cargarse manualmente.';
@endphp

<x-tabs-embedded
    :items="$items"
    :statuses="$statuses"
    toolbar-label="Estados de ítems"
    table-view="orders.items.partials.table"
    :table-data="[
        'order' => $order,
        'trailQuery' => $trailQuery,
    ]"
    :empty-message="$emptyMessage"
    :add-url="$addUrl"
    add-label="Agregar ítem"
    :summary-items="$summaryItems"
    :extra-help="$extraHelp"
/>