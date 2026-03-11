<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\TenantScoped;
use App\Models\Concerns\ResolvesTenantRouteBinding;

class Order extends Model
{
    use SoftDeletes;
    use TenantScoped;
    use ResolvesTenantRouteBinding;

    protected $fillable = [
        'tenant_id',
        'party_id',
        'kind',
        'number',
        'status',
        'ordered_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ordered_at' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function calculateTotal(): float
    {
        return (float) $this->items->sum('subtotal');
    }

    public function getTotalAttribute(): float
    {
        return $this->calculateTotal();
    }
}