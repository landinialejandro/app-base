<?php

// FILE: app/Support/Shops/ShopPublishedCatalogReader.php | V1

namespace App\Support\Shops;

use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class ShopPublishedCatalogReader
{
    public function activeShopForTenant(Tenant $tenant): ?Shop
    {
        return Shop::query()
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

    public function visibleItemsForShop(Shop $shop): Collection
    {
        return ShopItem::query()
            ->with('product')
            ->where('tenant_id', $shop->tenant_id)
            ->where('self_service_shop_id', $shop->id)
            ->where('status', ShopItem::STATUS_PUBLISHED)
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