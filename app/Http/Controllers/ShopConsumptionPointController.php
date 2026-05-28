<?php

// FILE: app/Http/Controllers/ShopConsumptionPointController.php | V1

namespace App\Http\Controllers;

use App\Http\Requests\StoreShopConsumptionPointRequest;
use App\Http\Requests\UpdateShopConsumptionPointRequest;
use App\Models\Shop;
use App\Models\ShopConsumptionPoint;
use Illuminate\Http\RedirectResponse;

class ShopConsumptionPointController extends Controller
{
    public function store(StoreShopConsumptionPointRequest $request, Shop $shop): RedirectResponse
    {
        $this->authorize('update', $shop);

        ShopConsumptionPoint::query()->create(array_merge($request->validatedData(), [
            'tenant_id' => $shop->tenant_id,
            'self_service_shop_id' => $shop->id,
        ]));

        return redirect()
            ->route('shops.show', ['shop' => $shop, 'return_tab' => 'consumption-points'])
            ->with('success', 'Punto de consumo creado correctamente.');
    }

    public function update(
        UpdateShopConsumptionPointRequest $request,
        Shop $shop,
        ShopConsumptionPoint $point
    ): RedirectResponse {
        $this->authorize('update', $shop);
        $this->assertPointBelongsToShop($shop, $point);

        $point->update($request->validatedData());

        return redirect()
            ->route('shops.show', ['shop' => $shop, 'return_tab' => 'consumption-points'])
            ->with('success', 'Punto de consumo actualizado correctamente.');
    }

    private function assertPointBelongsToShop(Shop $shop, ShopConsumptionPoint $point): void
    {
        abort_unless(
            (int) $point->self_service_shop_id === (int) $shop->id
            && (string) $point->tenant_id === (string) $shop->tenant_id,
            404
        );
    }
}
