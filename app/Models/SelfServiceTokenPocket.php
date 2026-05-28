<?php

// FILE: app/Models/SelfServiceTokenPocket.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SelfServiceTokenPocket extends Model
{
    use SoftDeletes;
    use TenantScoped;

    public const STATUS_ACTIVE = 'active';

    protected $fillable = [
        'tenant_id',
        'self_service_customer_account_id',
        'self_service_store_customer_id',
        'product_id',
        'quantity_available',
        'unit_label_snapshot',
        'unit_seconds_snapshot',
        'status',
        'meta',
    ];

    protected $casts = [
        'quantity_available' => 'decimal:2',
        'unit_seconds_snapshot' => 'integer',
        'meta' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SelfServiceCustomerAccount::class, 'self_service_customer_account_id');
    }

    public function storeCustomer(): BelongsTo
    {
        return $this->belongsTo(SelfServiceStoreCustomer::class, 'self_service_store_customer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SelfServiceTokenPocketMovement::class, 'self_service_token_pocket_id')
            ->orderBy('id');
    }
}
