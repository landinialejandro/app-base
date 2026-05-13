<?php

// FILE: app/Models/Product.php | V7

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use ResolvesTenantRouteBinding;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'description',
        'price',
        'kind',
        'unit_label',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function usedAsComponentIn(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'component_product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order')->latest('id');
    }
}