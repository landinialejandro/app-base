<?php

// FILE: app/Models/SelfServiceTokenPocketMovement.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfServiceTokenPocketMovement extends Model
{
    use TenantScoped;

    public const TYPE_PURCHASE_CREDIT = 'purchase_credit';

    protected $fillable = [
        'tenant_id',
        'self_service_token_pocket_id',
        'self_service_cart_id',
        'self_service_cart_item_id',
        'order_id',
        'order_item_id',
        'product_id',
        'movement_type',
        'quantity',
        'balance_after',
        'unit_label_snapshot',
        'unit_seconds_snapshot',
        'source_type',
        'source_id',
        'idempotency_key',
        'notes',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'unit_seconds_snapshot' => 'integer',
        'meta' => 'array',
    ];

    public function pocket(): BelongsTo
    {
        return $this->belongsTo(SelfServiceTokenPocket::class, 'self_service_token_pocket_id');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(SelfServiceCart::class, 'self_service_cart_id');
    }

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(SelfServiceCartItem::class, 'self_service_cart_item_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
