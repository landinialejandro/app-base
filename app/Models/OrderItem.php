<?php

// FILE: app/Models/OrderItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\TenantScoped;
use App\Models\Concerns\ResolvesTenantRouteBinding;

class OrderItem extends Model
{
    use SoftDeletes;
    use TenantScoped;
    use ResolvesTenantRouteBinding;

    protected $fillable = [
        'order_id',
        'product_id',
        'position',
        'kind',
        'description',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }

}