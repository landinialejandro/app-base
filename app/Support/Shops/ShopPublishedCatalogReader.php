<?php

// FILE: app/Support/Shops/ShopPublishedCatalogReader.php | V3

namespace App\Support\Shops;

use App\Models\Product;
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
        if (! $shop->isActive()) {
            return collect();
        }

        return ShopItem::query()
            ->with([
                'product' => function ($query) {
                    $query->with([
                        'attachments' => function ($query) {
                            $query
                                ->where('kind', 'shop')
                                ->where('is_image', true)
                                ->ordered();
                        },
                    ]);
                },
            ])
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

    public function visibleItemForProductInActiveShop(Tenant $tenant, Product $product): ?ShopItem
    {
        if ((int) $product->tenant_id !== (int) $tenant->id) {
            return null;
        }

        if ($product->is_active !== true) {
            return null;
        }

        $shop = $this->activeShopForTenant($tenant);

        if (! $shop) {
            return null;
        }

        return ShopItem::query()
            ->where('tenant_id', $tenant->id)
            ->where('self_service_shop_id', $shop->id)
            ->where('product_id', $product->id)
            ->where('status', ShopItem::STATUS_PUBLISHED)
            ->where('is_visible', true)
            ->whereHas('product', function ($query) use ($tenant) {
                $query
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true);
            })
            ->first();
    }
}