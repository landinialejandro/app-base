<?php

// FILE: app/Models/InventoryMaterialFlow.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMaterialFlow extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'order_id',
        'order_item_id',
        'inventory_movement_id',
        'flow_type',
        'quantity',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
