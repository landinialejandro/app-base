<?php

// FILE: app/Http/Controllers/OrderItemController.php | V4

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Catalogs\ProductCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderItemController extends Controller
{
    public function create(Order $order)
    {
        $this->authorize('update', $order);

        $products = Product::orderBy('name')->get();

        $item = new OrderItem([
            'kind' => ProductCatalog::KIND_PRODUCT,
            'quantity' => 1,
            'unit_price' => 0,
        ]);

        return view('orders.items.create', compact('order', 'item', 'products'));
    }

    public function store(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $tenant = app('tenant');

        $data = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'position' => ['nullable', 'integer', 'min:1'],
            'kind' => [
                'required',
                Rule::in(ProductCatalog::kinds()),
            ],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['position'] = $data['position'] ?? (($order->items()->max('position') ?? 0) + 1);

        $item = new OrderItem($data);
        $item->tenant_id = $order->tenant_id;
        $item->order_id = $order->id;
        $item->save();

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Ítem agregado correctamente.');
    }

    public function edit(Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless($item->order_id === $order->id, 404);

        $products = Product::orderBy('name')->get();

        return view('orders.items.edit', compact('order', 'item', 'products'));
    }

    public function update(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless($item->order_id === $order->id, 404);

        $tenant = app('tenant');

        $data = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'position' => ['nullable', 'integer', 'min:1'],
            'kind' => [
                'required',
                Rule::in(ProductCatalog::kinds()),
            ],
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
        $this->authorize('update', $order);

        abort_unless($item->order_id === $order->id, 404);

        $item->delete();

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Ítem eliminado correctamente.');
    }
}
