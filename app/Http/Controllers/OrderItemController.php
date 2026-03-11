<?php

// FILE: app/Http/Controllers/OrderItemController.php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function create(Order $order)
    {
        $products = Product::orderBy('name')->get();

        return view('orders.items.create', compact('order', 'products'));
    }

    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'position' => ['nullable', 'integer', 'min:1'],
            'kind' => ['required', 'in:product,service'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['tenant_id'] = $order->tenant_id;
        $data['order_id'] = $order->id;
        $data['position'] = $data['position'] ?? (($order->items()->max('position') ?? 0) + 1);

        $order->items()->create($data);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Ítem agregado correctamente.');
    }

    public function edit(Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id, 404);

        $products = Product::orderBy('name')->get();

        return view('orders.items.edit', compact('order', 'item', 'products'));
    }

    public function update(Request $request, Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id, 404);

        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'position' => ['nullable', 'integer', 'min:1'],
            'kind' => ['required', 'in:product,service'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $item->update($data);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id, 404);

        $item->delete();

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Ítem eliminado correctamente.');
    }
}