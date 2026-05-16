<?php

// FILE: app/Support/Orders/OrderIndexContext.php | V1

namespace App\Support\Orders;

use App\Support\Catalogs\OrderCatalog;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
use Illuminate\Http\Request;

class OrderIndexContext
{
    public static function fromRequest(Request $request, ?string $defaultCreateGroup = null): array
    {
        $isServiceUniverse = $request->routeIs('service.orders.*');
        $isProductionUniverse = $request->routeIs('production.orders.*');

        $currentGroup = match (true) {
            $isServiceUniverse => OrderCatalog::GROUP_SERVICE,
            $isProductionUniverse => OrderCatalog::GROUP_PRODUCTION,
            default => $request->get('group'),
        };

        $isServiceContext = $isServiceUniverse || $currentGroup === OrderCatalog::GROUP_SERVICE;
        $isProductionContext = $isProductionUniverse || $currentGroup === OrderCatalog::GROUP_PRODUCTION;

        $pageTitle = match (true) {
            $isServiceContext => 'Órdenes de servicio',
            $isProductionContext => 'Órdenes de producción',
            default => 'Órdenes',
        };

        $createLabel = match (true) {
            $isServiceContext => 'Nueva orden de servicio',
            $isProductionContext => 'Nueva orden de producción',
            default => 'Nueva orden',
        };

        $emptyMessage = match (true) {
            $isServiceContext => 'No hay órdenes de servicio cargadas.',
            $isProductionContext => 'No hay órdenes de producción cargadas.',
            default => 'No hay órdenes cargadas.',
        };

        $breadcrumbItems = match (true) {
            $isServiceUniverse => [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Servicio y mantenimiento', 'url' => route('service.index')],
                ['label' => 'Órdenes de servicio'],
            ],
            $isProductionUniverse => [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Producción', 'url' => route('production.index')],
                ['label' => 'Órdenes de producción'],
            ],
            default => [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => $pageTitle],
            ],
        };

        $indexRouteName = match (true) {
            $isServiceUniverse => 'service.orders.index',
            $isProductionUniverse => 'production.orders.index',
            default => 'orders.index',
        };

        $createRouteName = match (true) {
            $isServiceUniverse => 'service.orders.create',
            $isProductionUniverse => 'production.orders.create',
            default => 'orders.create',
        };

        $showRouteName = match (true) {
            $isServiceUniverse => 'service.orders.show',
            $isProductionUniverse => 'production.orders.show',
            default => 'orders.show',
        };

        $trailBase = match (true) {
            $isServiceUniverse => OrderNavigationTrail::serviceOrdersBase(),
            $isProductionUniverse => OrderNavigationTrail::productionOrdersBase(),
            default => OrderNavigationTrail::ordersBase(),
        };

        $trailQuery = NavigationTrail::toQuery($trailBase);

        $createGroup = match (true) {
            $isServiceContext => OrderCatalog::GROUP_SERVICE,
            $isProductionContext => OrderCatalog::GROUP_PRODUCTION,
            default => $defaultCreateGroup,
        };

        $createQuery = $isServiceUniverse || $isProductionUniverse
            ? $trailQuery
            : array_merge($trailQuery, ['group' => $createGroup]);

        return [
            'isServiceUniverse' => $isServiceUniverse,
            'isProductionUniverse' => $isProductionUniverse,
            'currentGroup' => $currentGroup,
            'isServiceContext' => $isServiceContext,
            'isProductionContext' => $isProductionContext,
            'pageTitle' => $pageTitle,
            'createLabel' => $createLabel,
            'emptyMessage' => $emptyMessage,
            'breadcrumbItems' => $breadcrumbItems,
            'indexRouteName' => $indexRouteName,
            'createRouteName' => $createRouteName,
            'showRouteName' => $showRouteName,
            'trailQuery' => $trailQuery,
            'createGroup' => $createGroup,
            'showGroupFilter' => ! $isServiceUniverse && ! $isProductionUniverse,
            'createQuery' => $createQuery,
        ];
    }
}
