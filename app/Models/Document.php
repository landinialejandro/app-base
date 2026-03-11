<?php

// FILE: app/Models/Document.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\TenantScoped;
use App\Models\Concerns\ResolvesTenantRouteBinding;

class Document extends Model
{
    use SoftDeletes;
    use TenantScoped;
    use ResolvesTenantRouteBinding;

    protected $fillable = [
        'tenant_id',
        'party_id',
        'order_id',
        'kind',
        'number',
        'status',
        'issued_at',
        'due_at',
        'currency_code',
        'subtotal',
        'tax_total',
        'total',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}