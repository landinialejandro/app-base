<?php

// FILE: app/Models/Order.php

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use ResolvesTenantRouteBinding;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'party_id',
        'asset_id',
        'kind',
        'number',
        'sequence_prefix',
        'point_of_sale',
        'sequence_number',
        'status',
        'ordered_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ordered_at' => 'date',
        'sequence_number' => 'integer',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
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
