<?php

// FILE: app/Models/ProductComponent.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductComponent extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'component_product_id',
        'quantity',
        'unit_label',
        'is_required',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'is_required' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }
}