<?php

// FILE: app/Models/InventoryMovement.php | V1

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    use ResolvesTenantRouteBinding;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'order_id',
        'document_id',
        'kind',
        'quantity',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isIngreso(): bool
    {
        return $this->kind === 'ingresar';
    }

    public function isConsumo(): bool
    {
        return $this->kind === 'consumir';
    }

    public function isEntrega(): bool
    {
        return $this->kind === 'entregar';
    }
}

// FILE: app/Models/InventoryMovement.php | V1
