<?php

// FILE: app/Http/Controllers/OrderItemController.php | V16

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderItemController extends Controller
{
    public function create(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        abort_if(
            OrderCatalog::isReadonlyStatus($order->status),
            422,
            'No se pueden agregar ítems a una orden en estado readonly.'
        );

        $supportsProductsModule = TenantModuleAccess::isEnabled(ModuleCatalog::PRODUCTS, app('tenant'));
        $security = app(Security::class);
        $user = auth()->user();

        $products = $supportsProductsModule
            ? $security
                ->scope($user, 'products.viewAny', Product::query())
                ->where('tenant_id', $order->tenant_id)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get()
            : collect();

        $item = new OrderItem([
            'position' => ((int) $order->items()->max('position')) + 1,
            'quantity' => 1,
            'status' => 'pending',
            'unit_price' => null,
        ]);

        $navigationTrail = OrderNavigationTrail::itemCreate($request, $order);

        return view('orders.items.create', compact(
            'order',
            'item',
            'products',
            'navigationTrail',
            'supportsProductsModule',
        ));
    }

    public function store(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        abort_if(
            OrderCatalog::isReadonlyStatus($order->status),
            422,
            'No se pueden agregar ítems a una orden en estado readonly.'
        );

        $supportsProductsModule = TenantModuleAccess::isEnabled(ModuleCatalog::PRODUCTS, app('tenant'));
        $security = app(Security::class);
        $user = auth()->user();

        $productRules = ['nullable', 'integer'];

        if ($supportsProductsModule) {
            $productRules[] = Rule::exists('products', 'id')->where(function ($query) use ($order) {
                $query->where('tenant_id', $order->tenant_id)
                    ->whereNull('deleted_at');
            });
        }

        $data = $request->validate([
            'product_id' => $productRules,
            'position' => ['required', 'integer', 'min:1'],
            'kind' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        if (! $supportsProductsModule) {
            $data['product_id'] = null;
        }

        if (! empty($data['product_id'])) {
            $security
                ->scope($user, 'products.viewAny', Product::query())
                ->where('tenant_id', $order->tenant_id)
                ->whereNull('deleted_at')
                ->whereKey($data['product_id'])
                ->firstOrFail();
        }

        $data['tenant_id'] = $order->tenant_id;
        $data['order_id'] = $order->id;
        $data['status'] = 'pending';

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

        abort_if(
            OrderCatalog::isReadonlyStatus($order->status),
            422,
            'No se pueden editar ítems de una orden en estado readonly.'
        );

        abort_if(
            $item->hasInventoryMovements(),
            422,
            'La línea ya tiene movimientos registrados y no puede editarse.'
        );

        $supportsProductsModule = TenantModuleAccess::isEnabled(ModuleCatalog::PRODUCTS, app('tenant'));
        $security = app(Security::class);
        $user = auth()->user();

        $products = $supportsProductsModule
            ? $security
                ->scope($user, 'products.viewAny', Product::query())
                ->where('tenant_id', $order->tenant_id)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get()
            : collect();

        $navigationTrail = OrderNavigationTrail::itemEdit($request, $order, $item);

        return view('orders.items.edit', compact(
            'order',
            'item',
            'products',
            'navigationTrail',
            'supportsProductsModule',
        ));
    }

    public function update(Request $request, Order $order, OrderItem $item)
    {
        $this->authorize('update', $order);

        abort_unless((int) $item->order_id === (int) $order->id, 404);

        abort_if(
            OrderCatalog::isReadonlyStatus($order->status),
            422,
            'No se pueden editar ítems de una orden en estado readonly.'
        );

        abort_if(
            $item->hasInventoryMovements(),
            422,
            'La línea ya tiene movimientos registrados y no puede editarse.'
        );

        $supportsProductsModule = TenantModuleAccess::isEnabled(ModuleCatalog::PRODUCTS, app('tenant'));
        $security = app(Security::class);
        $user = auth()->user();

        $productRules = ['nullable', 'integer'];

        if ($supportsProductsModule) {
            $productRules[] = Rule::exists('products', 'id')->where(function ($query) use ($order) {
                $query->where('tenant_id', $order->tenant_id)
                    ->whereNull('deleted_at');
            });
        }

        $data = $request->validate([
            'product_id' => $productRules,
            'position' => ['required', 'integer', 'min:1'],
            'kind' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        if (! $supportsProductsModule) {
            $data['product_id'] = null;
        }

        if (! empty($data['product_id'])) {
            $security
                ->scope($user, 'products.viewAny', Product::query())
                ->where('tenant_id', $order->tenant_id)
                ->whereNull('deleted_at')
                ->whereKey($data['product_id'])
                ->firstOrFail();
        }

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

        abort_if(
            OrderCatalog::isReadonlyStatus($order->status),
            422,
            'No se pueden eliminar ítems de una orden en estado readonly.'
        );

        abort_if(
            $item->hasInventoryMovements(),
            422,
            'La línea ya tiene movimientos registrados y no puede eliminarse.'
        );

        $item->delete();

        $navigationTrail = OrderNavigationTrail::show($request, $order);

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem eliminado correctamente.');
    }
}
