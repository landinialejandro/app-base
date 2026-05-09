<?php

// FILE: app/Http/Controllers/ServiceDashboardController.php | V1

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;

class ServiceDashboardController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');
        $user = auth()->user();
        $security = app(Security::class);

        abort_unless(
            TenantModuleAccess::isEnabled(ModuleCatalog::SERVICE_MAINTENANCE, $tenant)
                && $security->allows($user, ModuleCatalog::SERVICE_MAINTENANCE.'.viewAny'),
            403
        );

        $canViewServiceOrders = $security->allows($user, ModuleCatalog::ORDERS.'.viewAny');

        $canCreateServiceOrders = $security->allows(
            $user,
            ModuleCatalog::ORDERS.'.create',
            Order::class,
            ['kind' => OrderCatalog::GROUP_SERVICE]
        );

        $serviceOrdersCount = $canViewServiceOrders
            ? $security
                ->scope($user, ModuleCatalog::ORDERS.'.viewAny', Order::query())
                ->where('group', OrderCatalog::GROUP_SERVICE)
                ->count()
            : null;

        return view('service.dashboard', compact(
            'canViewServiceOrders',
            'canCreateServiceOrders',
            'serviceOrdersCount',
        ));
    }
}