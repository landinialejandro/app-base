<?php

// FILE: app/Http/Controllers/OrderItemController.php | V7

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Navigation\NavigationTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderItemController extends Controller
{
    public function create(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $products = Product::query()
            ->where('tenant_id', $order->tenant_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $item = new OrderItem([
            'position' => ((int) $order->items()->max('position')) + 1,
            'quantity' => 1,
            'unit_price' => null,
        ]);

        $navigationTrail = NavigationTrail::fromRequest($request);

        if (empty($navigationTrail) || ! NavigationTrail::hasNode($navigationTrail, 'orders.show', $order->id)) {
            $navigationTrail = NavigationTrail::base([
                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                NavigationTrail::makeNode('orders.index', null, 'Órdenes', route('orders.index')),
                NavigationTrail::makeNode(
                    'orders.show',
                    $order->id,
                    $order->number ?: 'Orden #'.$order->id,
                    route('orders.show', ['order' => $order])
                ),
            ]);

            $navigationTrail = NavigationTrail::replaceCurrentUrl(
                $navigationTrail,
                route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            );
        }

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'orders.items.create',
                $order->id,
                'Agregar ítem',
                route('orders.items.create', ['order' => $order])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('orders.items.create', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
        );

        return view('orders.items.create', compact('order', 'item', 'products', 'navigationTrail'));
    }

    public function store(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $navigationTrail = NavigationTrail::fromRequest($request);

        $data = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($order) {
                    $query->where('tenant_id', $order->tenant_id)
                        ->whereNull('deleted_at');
                }),
            ],
            'position' => ['required', 'integer', 'min:1'],
            'kind' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['tenant_id'] = $order->tenant_id;
        $data['order_id'] = $order->id;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        OrderItem::create($data);

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'orders.show',
                $order->id,
                $order->number ?: 'Orden #'.$order->id,
                route('orders.show', ['order' => $order])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
        );

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem agregado correctamente.');
    }

    public function edit(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless((int) $item->order_id === (int) $order->id, 404);

        $products = Product::query()
            ->where('tenant_id', $order->tenant_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $navigationTrail = NavigationTrail::fromRequest($request);

        if (empty($navigationTrail) || ! NavigationTrail::hasNode($navigationTrail, 'orders.show', $order->id)) {
            $navigationTrail = NavigationTrail::base([
                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                NavigationTrail::makeNode('orders.index', null, 'Órdenes', route('orders.index')),
                NavigationTrail::makeNode(
                    'orders.show',
                    $order->id,
                    $order->number ?: 'Orden #'.$order->id,
                    route('orders.show', ['order' => $order])
                ),
            ]);

            $navigationTrail = NavigationTrail::replaceCurrentUrl(
                $navigationTrail,
                route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            );
        }

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'orders.items.edit',
                $item->id,
                'Editar ítem',
                route('orders.items.edit', ['order' => $order, 'item' => $item])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('orders.items.edit', ['order' => $order, 'item' => $item] + NavigationTrail::toQuery($navigationTrail))
        );

        return view('orders.items.edit', compact('order', 'item', 'products', 'navigationTrail'));
    }

    public function update(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless((int) $item->order_id === (int) $order->id, 404);

        $navigationTrail = NavigationTrail::fromRequest($request);

        $data = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($order) {
                    $query->where('tenant_id', $order->tenant_id)
                        ->whereNull('deleted_at');
                }),
            ],
            'position' => ['required', 'integer', 'min:1'],
            'kind' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['updated_by'] = auth()->id();

        $item->update($data);

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'orders.show',
                $order->id,
                $order->number ?: 'Orden #'.$order->id,
                route('orders.show', ['order' => $order])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
        );

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless((int) $item->order_id === (int) $order->id, 404);

        $navigationTrail = NavigationTrail::fromRequest($request);

        $item->delete();

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'orders.show',
                $order->id,
                $order->number ?: 'Orden #'.$order->id,
                route('orders.show', ['order' => $order])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
        );

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem eliminado correctamente.');
    }
}
