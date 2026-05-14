<?php

// FILE: app/Support/SelfServiceSales/SelfServiceShopPublisher.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceShop;
use Illuminate\Support\Facades\DB;

class SelfServiceShopPublisher
{
    public function activate(SelfServiceShop $shop): SelfServiceShop
    {
        return DB::transaction(function () use ($shop): SelfServiceShop {
            $shop = SelfServiceShop::query()
                ->whereKey($shop->id)
                ->lockForUpdate()
                ->firstOrFail();

            SelfServiceShop::query()
                ->where('tenant_id', $shop->tenant_id)
                ->where('status', SelfServiceShop::STATUS_ACTIVE)
                ->whereKeyNot($shop->id)
                ->update([
                    'status' => SelfServiceShop::STATUS_INACTIVE,
                    'updated_at' => now(),
                ]);

            $shop->forceFill([
                'status' => SelfServiceShop::STATUS_ACTIVE,
                'published_at' => $shop->published_at ?: now(),
            ])->save();

            return $shop->fresh();
        });
    }
}
