<?php

// FILE: app/Models/SelfServiceCartItem.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SelfServiceCartItem extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'self_service_cart_id',
        'self_service_shop_item_id',
        'product_id',
        'quantity',
        'unit_price_snapshot',
        'display_name_snapshot',
        'unit_label_snapshot',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_snapshot' => 'decimal:2',
        'meta' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(SelfServiceCart::class, 'self_service_cart_id');
    }

    public function shopItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class, 'self_service_shop_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
