<?php

// FILE: app/Http/Controllers/ProductionDashboardController.php | V1

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Auth\Security;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;

class ProductionDashboardController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');
        $user = auth()->user();
        $security = app(Security::class);

        $canViewProductionOrders = $security->allows($user, ModuleCatalog::ORDERS.'.viewAny');

        $canCreateProductionOrders = $security->allows(
            $user,
            ModuleCatalog::ORDERS.'.create',
            Order::class,
            ['kind' => OrderCatalog::GROUP_PRODUCTION]
        );

        $productionOrdersCount = $canViewProductionOrders
            ? $security
                ->scope($user, ModuleCatalog::ORDERS.'.viewAny', Order::query())
                ->where('group', OrderCatalog::GROUP_PRODUCTION)
                ->count()
            : null;

        return view('production.dashboard', compact(
            'tenant',
            'canViewProductionOrders',
            'canCreateProductionOrders',
            'productionOrdersCount',
        ));
    }
}