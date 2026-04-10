{{-- FILE: resources/views/orders/partials/embedded-tabs.blade.php | V7 --}}

@php
    use App\Support\Catalogs\OrderCatalog;
    use App\Models\Order;
    use App\Support\Auth\Security;

    $orders = $orders ?? collect();

    $showParty = $showParty ?? false;
    $showAsset = $showAsset ?? true;

    $emptyMessage = $emptyMessage ?? 'No hay órdenes para mostrar.';
    $allLabel = $allLabel ?? 'Todas';

    $kinds = OrderCatalog::kindLabels();
    $tabsId = $tabsId ?? 'orders-tabs-' . uniqid();
    $trailQuery = $trailQuery ?? [];
    $createBaseQuery = $createBaseQuery ?? [];

    $allowedCreateKinds = collect(OrderCatalog::kinds())
        ->filter(
            fn(string $kind) => app(Security::class)->allows(auth()->user(), 'orders.create', Order::class, [
                'kind' => $kind,
            ]),
        )
        ->values();

    $canCreateOrders = $allowedCreateKinds->isNotEmpty();
    $defaultCreateKind = $allowedCreateKinds->first();
@endphp

<div class="tabs" data-tabs>
    @php
        $toolbarActions = null;

        if ($canCreateOrders) {
            $toolbarActions = route('orders.create', $createBaseQuery + $trailQuery + ['kind' => $defaultCreateKind]);
        }
    @endphp
