<?php

// FILE: app/Models/Product.php

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use TenantScoped;
    use ResolvesTenantRouteBinding;
    use SoftDeletes;

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
}