<?php

// FILE: app/Http/Controllers/OrderItemController.php | V9

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
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

        $navigationTrail = OrderNavigationTrail::itemCreate($request, $order);

        return view('orders.items.create', compact('order', 'item', 'products', 'navigationTrail'));
    }

    public function store(Request $request, Order $order)
    {
        $this->authorize('update', $order);

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

        $navigationTrail = OrderNavigationTrail::show($request, $order);

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

        $navigationTrail = OrderNavigationTrail::itemEdit($request, $order, $item);

        return view('orders.items.edit', compact('order', 'item', 'products', 'navigationTrail'));
    }

    public function update(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless((int) $item->order_id === (int) $order->id, 404);

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

        $navigationTrail = OrderNavigationTrail::show($request, $order);

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless((int) $item->order_id === (int) $order->id, 404);

        $item->delete();

        $navigationTrail = OrderNavigationTrail::show($request, $order);

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem eliminado correctamente.');
    }
}
