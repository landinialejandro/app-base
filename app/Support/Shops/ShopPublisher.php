<?php

// FILE: app/Support/Shops/ShopPublisher.php | V1

namespace App\Support\Shops;

use App\Models\Shop;
use Illuminate\Support\Facades\DB;

class ShopPublisher
{
    public function activate(Shop $shop): Shop
    {
        return DB::transaction(function () use ($shop): Shop {
            $shop = Shop::query()
                ->whereKey($shop->id)
                ->lockForUpdate()
                ->firstOrFail();

            Shop::query()
                ->where('tenant_id', $shop->tenant_id)
                ->where('status', Shop::STATUS_ACTIVE)
                ->whereKeyNot($shop->id)
                ->update([
                    'status' => Shop::STATUS_INACTIVE,
                    'updated_at' => now(),
                ]);

            $shop->forceFill([
                'status' => Shop::STATUS_ACTIVE,
                'published_at' => $shop->published_at ?: now(),
            ])->save();

            return $shop->fresh();
        });
    }
}