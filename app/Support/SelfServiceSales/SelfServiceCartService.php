<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCartService.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceCart;
use App\Models\SelfServiceCartItem;
use App\Models\SelfServiceCustomerAccount;
use App\Models\SelfServiceStoreCustomer;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SelfServiceCartService
{
    public const MESSAGE_LOGIN_REQUIRED = 'Ingresá como cliente para operar el carrito.';
    public const MESSAGE_OPERATION_DISABLED = 'Tu cuenta externa está reconocida para esta tienda, pero la operación comercial todavía no está habilitada.';
    public const MESSAGE_NOT_AVAILABLE = 'El producto ya no está disponible en la tienda.';
    public const MESSAGE_EMPTY_CART = 'El carrito está vacío.';

    public function currentCart(Request $request, Tenant $tenant): SelfServiceCart
    {
        $context = $this->authorizedContext($request, $tenant);

        return $this->activeCart($tenant, $context['account'], $context['store_customer']);
    }

    public function addItem(Request $request, Tenant $tenant, int $shopItemId, int $quantity): SelfServiceCart
    {
        $context = $this->authorizedContext($request, $tenant);
        $cart = $this->activeCart($tenant, $context['account'], $context['store_customer']);
        $shopItem = $this->publicShopItem($tenant, $shopItemId);
        $quantity = max(1, $quantity);

        $line = SelfServiceCartItem::withTrashed()
            ->where('tenant_id', $tenant->id)
            ->where('self_service_cart_id', $cart->id)
            ->where('self_service_shop_item_id', $shopItem->id)
            ->first();

        $lineQuantity = $quantity;

        if ($line && ! $line->trashed()) {
            $lineQuantity = $line->quantity + $quantity;
        }

        $payload = [
            'tenant_id' => $tenant->id,
            'self_service_cart_id' => $cart->id,
            'self_service_shop_item_id' => $shopItem->id,
            'product_id' => $shopItem->product_id,
            'quantity' => $lineQuantity,
            'unit_price_snapshot' => (float) ($shopItem->displayPrice() ?? 0),
            'display_name_snapshot' => $shopItem->displayName(),
            'unit_label_snapshot' => $shopItem->product?->unit_label,
        ];

        if ($line) {
            if ($line->trashed()) {
                $line->restore();
            }

            $line->fill($payload)->save();
        } else {
            SelfServiceCartItem::query()->create($payload);
        }

        return $this->freshCart($cart);
    }

    public function updateItem(Request $request, Tenant $tenant, SelfServiceCartItem $cartItem, int $quantity): SelfServiceCart
    {
        $cart = $this->cartForItemAction($request, $tenant, $cartItem);

        $cartItem->update([
            'quantity' => max(1, $quantity),
        ]);

        return $this->freshCart($cart);
    }

    public function destroyItem(Request $request, Tenant $tenant, SelfServiceCartItem $cartItem): SelfServiceCart
    {
        $cart = $this->cartForItemAction($request, $tenant, $cartItem);

        $cartItem->delete();

        return $this->freshCart($cart);
    }

    public function clear(Request $request, Tenant $tenant): SelfServiceCart
    {
        $cart = $this->currentCart($request, $tenant);

        $cart->items()->delete();

        return $this->freshCart($cart);
    }

    public function simulateCheckout(Request $request, Tenant $tenant): SelfServiceCart
    {
        $cart = $this->currentCart($request, $tenant);

        if ($cart->items->isEmpty()) {
            throw new HttpException(422, self::MESSAGE_EMPTY_CART);
        }

        return $cart;
    }

    private function authorizedContext(Request $request, Tenant $tenant): array
    {
        $payload = $request->attributes->get('self_service_external_customer');

        if (! is_array($payload)) {
            throw new HttpException(403, self::MESSAGE_LOGIN_REQUIRED);
        }

        $account = $payload['account'] ?? null;
        $storeCustomer = $payload['store_customer'] ?? null;

        if (! $account instanceof SelfServiceCustomerAccount || ! $storeCustomer instanceof SelfServiceStoreCustomer) {
            throw new HttpException(403, self::MESSAGE_LOGIN_REQUIRED);
        }

        if ((string) $storeCustomer->tenant_id !== (string) $tenant->id || $storeCustomer->status !== SelfServiceStoreCustomer::STATUS_ACTIVE) {
            throw new HttpException(403, self::MESSAGE_LOGIN_REQUIRED);
        }

        if ($storeCustomer->operation_enabled !== true || ($payload['can_operate'] ?? false) !== true) {
            throw new HttpException(403, self::MESSAGE_OPERATION_DISABLED);
        }

        return [
            'account' => $account,
            'store_customer' => $storeCustomer,
        ];
    }

    private function activeCart(
        Tenant $tenant,
        SelfServiceCustomerAccount $account,
        SelfServiceStoreCustomer $storeCustomer
    ): SelfServiceCart {
        $cart = SelfServiceCart::query()
            ->where('tenant_id', $tenant->id)
            ->where('self_service_customer_account_id', $account->id)
            ->where('self_service_store_customer_id', $storeCustomer->id)
            ->where('status', SelfServiceCart::STATUS_ACTIVE)
            ->orderByDesc('id')
            ->first();

        if (! $cart) {
            $cart = SelfServiceCart::query()->create([
                'tenant_id' => $tenant->id,
                'self_service_customer_account_id' => $account->id,
                'self_service_store_customer_id' => $storeCustomer->id,
                'status' => SelfServiceCart::STATUS_ACTIVE,
            ]);
        }

        return $this->freshCart($cart);
    }

    private function publicShopItem(Tenant $tenant, int $shopItemId): ShopItem
    {
        $shopItem = ShopItem::query()
            ->with(['shop', 'product'])
            ->where('tenant_id', $tenant->id)
            ->whereKey($shopItemId)
            ->where('status', ShopItem::STATUS_PUBLISHED)
            ->where('is_visible', true)
            ->whereHas('shop', function ($query) use ($tenant) {
                $query
                    ->where('tenant_id', $tenant->id)
                    ->where('status', Shop::STATUS_ACTIVE);
            })
            ->whereHas('product', function ($query) use ($tenant) {
                $query
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true);
            })
            ->first();

        if (! $shopItem) {
            throw new HttpException(422, self::MESSAGE_NOT_AVAILABLE);
        }

        return $shopItem;
    }

    private function cartForItemAction(Request $request, Tenant $tenant, SelfServiceCartItem $cartItem): SelfServiceCart
    {
        $cart = $this->currentCart($request, $tenant);

        if (
            (string) $cartItem->tenant_id !== (string) $tenant->id
            || (int) $cartItem->self_service_cart_id !== (int) $cart->id
        ) {
            throw new HttpException(404, self::MESSAGE_NOT_AVAILABLE);
        }

        return $cart;
    }

    private function freshCart(SelfServiceCart $cart): SelfServiceCart
    {
        return $cart->fresh(['items']);
    }
}
