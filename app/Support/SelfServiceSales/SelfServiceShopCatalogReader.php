<?php

// FILE: app/Support/SelfServiceSales/SelfServiceShopCatalogReader.php | V2

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceShop;
use App\Models\SelfServiceShopItem;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class SelfServiceShopCatalogReader
{
    public function activeShopForTenant(Tenant $tenant): ?SelfServiceShop
    {
        return SelfServiceShop::query()
            ->where('tenant_id', $tenant->id)
            ->active()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    public function visibleItemsForTenant(Tenant $tenant): Collection
    {
        $shop = $this->activeShopForTenant($tenant);

        if (! $shop) {
            return collect();
        }

        return $this->visibleItemsForShop($shop);
    }

    public function visibleItemsForShop(SelfServiceShop $shop): Collection
    {
        return SelfServiceShopItem::query()
            ->with('product')
            ->where('tenant_id', $shop->tenant_id)
            ->where('self_service_shop_id', $shop->id)
            ->where('status', SelfServiceShopItem::STATUS_PUBLISHED)
            ->where('is_visible', true)
            ->whereHas('product', function ($query) use ($shop) {
                $query
                    ->where('tenant_id', $shop->tenant_id)
                    ->where('is_active', true);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
