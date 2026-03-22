<?php

// FILE: app/Http/Controllers/OrderItemController.php | V5

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Navigation\NavigationContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderItemController extends Controller
{
    public function create(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $products = Product::query()
            ->orderBy('name')
            ->get();

        $item = new OrderItem([
            'position' => ((int) $order->items()->max('position')) + 1,
            'quantity' => 1,
            'unit_price' => null,
        ]);

        $navigationContext = NavigationContext::resolveFromRequest($request, $order->tenant_id);

        return view('orders.items.create', compact('order', 'item', 'products', 'navigationContext'));
    }

    public function store(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $navigationContext = NavigationContext::resolveFromRequest($request, $order->tenant_id);

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

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationContext::routeParams($navigationContext))
            ->with('success', 'Ítem agregado correctamente.');
    }

    public function edit(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless((int) $item->order_id === (int) $order->id, 404);

        $products = Product::query()
            ->orderBy('name')
            ->get();

        $navigationContext = NavigationContext::resolveFromRequest($request, $order->tenant_id);

        return view('orders.items.edit', compact('order', 'item', 'products', 'navigationContext'));
    }

    public function update(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless((int) $item->order_id === (int) $order->id, 404);

        $navigationContext = NavigationContext::resolveFromRequest($request, $order->tenant_id);

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

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationContext::routeParams($navigationContext))
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless((int) $item->order_id === (int) $order->id, 404);

        $navigationContext = NavigationContext::resolveFromRequest($request, $order->tenant_id);

        $item->delete();

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationContext::routeParams($navigationContext))
            ->with('success', 'Ítem eliminado correctamente.');
    }
}
