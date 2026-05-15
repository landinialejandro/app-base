<?php

// FILE: app/Http/Controllers/ShopItemController.php | V1

namespace App\Http\Controllers;

use App\Events\OperationalRecordCreated;
use App\Events\OperationalRecordUpdated;
use App\Http\Requests\StoreShopItemRequest;
use App\Http\Requests\UpdateShopItemRequest;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Support\Auth\Security;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopItemController extends Controller
{
    public function create(Request $request, Shop $shop): View
    {
        $this->authorize('update', $shop);

        $products = app(Security::class)
            ->scope($request->user(), 'products.viewAny', Product::query())
            ->where('tenant_id', app('tenant')->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('shops.items.create', [
            'shop' => $shop,
            'item' => new ShopItem([
                'status' => ShopItem::STATUS_DRAFT,
                'use_product_price' => true,
                'sort_order' => 0,
            ]),
            'products' => $products,
        ]);
    }

    public function store(StoreShopItemRequest $request, Shop $shop): RedirectResponse
    {
        $data = $request->validatedData();

        $item = ShopItem::create(array_merge($data, [
            'tenant_id' => $shop->tenant_id,
            'self_service_shop_id' => $shop->id,
        ]));

        event(new OperationalRecordCreated(
            record: $item,
            actorUserId: $request->user()?->id,
        ));

        return redirect()
            ->route('shops.show', $shop)
            ->with('success', 'Artículo publicado creado correctamente.');
    }

    public function edit(Shop $shop, ShopItem $item): View
    {
        $this->authorize('update', $shop);
        $this->assertItemBelongsToShop($shop, $item);

        $item->loadMissing('product');

        return view('shops.items.edit', [
            'shop' => $shop,
            'item' => $item,
        ]);
    }

    public function update(UpdateShopItemRequest $request, Shop $shop, ShopItem $item): RedirectResponse
    {
        $this->assertItemBelongsToShop($shop, $item);

        $beforeAttributes = $item->getAttributes();

        $item->update($request->validatedData());

        event(new OperationalRecordUpdated(
            record: $item,
            beforeAttributes: $beforeAttributes,
            actorUserId: $request->user()?->id,
        ));

        return redirect()
            ->route('shops.show', $shop)
            ->with('success', 'Artículo publicado actualizado correctamente.');
    }

    public function destroy(Request $request, Shop $shop, ShopItem $item): RedirectResponse
    {
        $this->authorize('update', $shop);
        $this->assertItemBelongsToShop($shop, $item);

        $beforeAttributes = $item->getAttributes();

        $item->update([
            'status' => ShopItem::STATUS_HIDDEN,
            'is_visible' => false,
        ]);

        event(new OperationalRecordUpdated(
            record: $item,
            beforeAttributes: $beforeAttributes,
            actorUserId: $request->user()?->id,
        ));

        return redirect()
            ->route('shops.show', $shop)
            ->with('success', 'Artículo ocultado correctamente.');
    }

    protected function assertItemBelongsToShop(Shop $shop, ShopItem $item): void
    {
        abort_unless(
            (int) $item->self_service_shop_id === (int) $shop->id
            && $item->tenant_id === $shop->tenant_id,
            404
        );
    }
}