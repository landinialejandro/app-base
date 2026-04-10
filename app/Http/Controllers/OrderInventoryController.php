<?php

// FILE: app/Http/Controllers/OrderInventoryController.php | V3

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Support\Auth\Security;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\InventoryMovementService;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderInventoryController extends Controller
{
    public function consumir(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $product = $this->resolveProductFromOrder($order, (int) $data['product_id']);

        app(InventoryMovementService::class)->consumir(
            product: $product,
            quantity: $data['quantity'],
            notes: $data['notes'] ?? null,
            order: $order,
            createdBy: auth()->id(),
        );

        $navigationTrail = OrderNavigationTrail::show($request, $order);

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Movimiento de consumo registrado correctamente.');
    }

    public function entregar(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $product = $this->resolveProductFromOrder($order, (int) $data['product_id']);

        app(InventoryMovementService::class)->entregar(
            product: $product,
            quantity: $data['quantity'],
            notes: $data['notes'] ?? null,
            order: $order,
            createdBy: auth()->id(),
        );

        $navigationTrail = OrderNavigationTrail::show($request, $order);

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Movimiento de entrega registrado correctamente.');
    }

    protected function resolveProductFromOrder(Order $order, int $productId): Product
    {
        $order->loadMissing('items.product');

        $product = $order->items
            ->filter(fn ($item) => $item->product && $item->product->kind === ProductCatalog::KIND_PRODUCT)
            ->map(fn ($item) => $item->product)
            ->first(fn ($product) => (int) $product->id === $productId);

        abort_if(! $product, 422, 'El producto seleccionado no pertenece a los ítems físicos de esta orden.');

        app(Security::class)
            ->scope(auth()->user(), 'products.viewAny', Product::query())
            ->where('tenant_id', $order->tenant_id)
            ->whereNull('deleted_at')
            ->whereKey($product->id)
            ->firstOrFail();

        return $product;
    }
}
