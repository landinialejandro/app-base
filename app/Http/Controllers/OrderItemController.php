<?php

// FILE: app/Http/Controllers/OrderItemController.php | V24

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\LineItems\LineItemMath;
use App\Support\LineItems\LineItemValidationRules;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
use App\Support\Orders\OrdersHooks;
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
            ...app(LineItemValidationRules::class)->baseRules(),
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

        $data = $this->syncDerivedFields($data);

        OrderItem::create($data);

        if ($order->status === OrderCatalog::STATUS_DRAFT) {
            $order->update([
                'status' => OrderCatalog::STATUS_PENDING_APPROVAL,
                'updated_by' => auth()->id(),
            ]);
        }

        $navigationTrail = OrderNavigationTrail::show($request, $order->fresh());

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

        app(OrdersHooks::class)->beforeOrderItemEdit($order, $item);

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

        app(OrdersHooks::class)->beforeOrderItemEdit($order, $item);

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
            ...app(LineItemValidationRules::class)->baseRules(),
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

        $data = app(OrdersHooks::class)->beforeOrderItemUpdate($order, $item, $data);

        $data = $this->syncDerivedFields($data);

        $item->update($data);

        app(OrdersHooks::class)->afterOrderItemUpdate($order, $item);

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

        app(OrdersHooks::class)->beforeOrderItemDestroy($order, $item);

        $item->delete();

        $navigationTrail = OrderNavigationTrail::show($request, $order);

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem eliminado correctamente.');
    }

    protected function syncDerivedFields(array $data): array
    {
        $math = app(LineItemMath::class);

        $quantity = $math->normalizeQuantity($data['quantity'] ?? 0);
        $unitPrice = $math->normalizeMoney($data['unit_price'] ?? 0);

        $data['quantity'] = $quantity;
        $data['unit_price'] = $unitPrice;
        $data['subtotal'] = $math->lineTotal($quantity, $unitPrice);

        return $data;
    }
}