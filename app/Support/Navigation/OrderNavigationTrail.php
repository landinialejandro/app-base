<?php

// FILE: app/Support/Navigation/OrderNavigationTrail.php | V7

namespace App\Support\Navigation;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Task;
use Illuminate\Http\Request;

class OrderNavigationTrail
{
    public static function ordersBase(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('orders.index', null, 'Órdenes', route('orders.index')),
        ]);
    }

    public static function base(Order $order): array
    {
        $trail = self::ordersBase();

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'orders.show',
                $order->id,
                $order->number ?: 'Orden #'.$order->id,
                route('orders.show', ['order' => $order])
            )
        );
    }

protected static function contextualBase(Request $request, ?Task $task = null, ?Appointment $appointment = null): array
{
    if ($task) {
        return TaskNavigationTrail::base($task);
    }

    if ($appointment) {
        return AppointmentNavigationTrail::base($appointment);
    }

    return self::baseForRequest($request);
}

public static function create(Request $request, ?Appointment $appointment = null, ?Task $task = null): array
{
    $trail = NavigationTrail::fromRequest($request);

    if (empty($trail)) {
        $trail = self::contextualBase($request, $task, $appointment);
    }

    $routeName = self::routeNameForRequest($request, 'create');
    $label = $request->routeIs('service.orders.*')
        ? 'Nueva orden de servicio'
        : 'Nueva orden';

    return NavigationTrail::appendOrCollapse(
        $trail,
        NavigationTrail::makeNode(
            $routeName,
            'new',
            $label,
            route($routeName)
        )
    );
}

public static function show(Request $request, Order $order, ?Appointment $appointment = null, ?Task $task = null): array
{
    $trail = NavigationTrail::fromRequest($request);

    if (empty($trail)) {
        $trail = self::contextualBase($request, $task, $appointment);

        if ($trail === self::ordersBase()) {
            $trail = self::base($order);
        }
    }

    $trail = NavigationTrail::removeNodes($trail, [
        ['key' => 'orders.create', 'id' => 'new'],
        ['key' => 'service.orders.create', 'id' => 'new'],
        ['key' => 'orders.edit', 'id' => $order->id],
        ['key' => 'orders.items.create', 'id' => $order->id],
        ['key' => 'orders.items.edit'],
    ]);

    $routeName = self::routeNameForRequest($request, 'show');

    return NavigationTrail::appendOrCollapse(
        $trail,
        NavigationTrail::makeNode(
            $routeName,
            $order->id,
            $order->number ?: 'Orden #'.$order->id,
            route($routeName, ['order' => $order])
        )
    );
}

    public static function edit(Request $request, Order $order, ?Appointment $appointment = null, ?Task $task = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'orders.show', $order->id)) {
            $trail = self::show($request, $order, $appointment, $task);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'orders.edit',
                $order->id,
                'Editar',
                route('orders.edit', ['order' => $order])
            )
        );
    }

    public static function itemCreate(Request $request, Order $order): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'orders.show', $order->id)) {
            $trail = self::show($request, $order);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'orders.items.create',
                $order->id,
                'Agregar ítem',
                route('orders.items.create', ['order' => $order])
            )
        );
    }

    public static function itemEdit(Request $request, Order $order, OrderItem $item): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'orders.show', $order->id)) {
            $trail = self::show($request, $order);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'orders.items.edit',
                $item->id,
                'Editar ítem',
                route('orders.items.edit', ['order' => $order, 'item' => $item])
            )
        );
    }


public static function serviceOrdersBase(): array
{
    return NavigationTrail::base([
        NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
        NavigationTrail::makeNode('service.index', null, 'Servicio y mantenimiento', route('service.index')),
        NavigationTrail::makeNode('service.orders.index', null, 'Órdenes de servicio', route('service.orders.index')),
    ]);
}


    protected static function routeNameForRequest(Request $request, string $action): string
    {
        return $request->routeIs('service.orders.*')
            ? 'service.orders.'.$action
            : 'orders.'.$action;
    }


protected static function baseForRequest(Request $request): array
{
    return $request->routeIs('service.orders.*')
        ? self::serviceOrdersBase()
        : self::ordersBase();
}
}
