<?php

// FILE: app/Support/Navigation/OrderNavigationTrail.php | V2

namespace App\Support\Navigation;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\OrderItem;
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

    public static function create(Request $request, ?Appointment $appointment = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = $appointment
                ? AppointmentNavigationTrail::base($appointment)
                : self::ordersBase();
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'orders.create',
                'new',
                'Nueva orden',
                route('orders.create')
            )
        );
    }

    public static function show(Request $request, Order $order, ?Appointment $appointment = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = $appointment
                ? AppointmentNavigationTrail::base($appointment)
                : self::ordersBase();
        }

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

    public static function edit(Request $request, Order $order, ?Appointment $appointment = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'orders.show', $order->id)) {
            $trail = self::show($request, $order, $appointment);
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
            $trail = self::base($order);
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
            $trail = self::base($order);
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
}
