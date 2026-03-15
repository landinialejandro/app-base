<?php

// FILE: app/Models/DocumentItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\TenantScoped;
use App\Models\Concerns\ResolvesTenantRouteBinding;

class DocumentItem extends Model
{
    use SoftDeletes;
    use TenantScoped;
    use ResolvesTenantRouteBinding;

    protected $fillable = [
        'tenant_id',
        'document_id',
        'product_id',
        'position',
        'kind',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}