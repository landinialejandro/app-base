<?php

// FILE: app/Models/SelfServiceCart.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SelfServiceCart extends Model
{
    use SoftDeletes;
    use TenantScoped;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_CHECKED_OUT = 'checked_out';

    protected $fillable = [
        'tenant_id',
        'self_service_customer_account_id',
        'self_service_store_customer_id',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SelfServiceCustomerAccount::class, 'self_service_customer_account_id');
    }

    public function selfServiceCustomerAccount(): BelongsTo
    {
        return $this->account();
    }

    public function storeCustomer(): BelongsTo
    {
        return $this->belongsTo(SelfServiceStoreCustomer::class, 'self_service_store_customer_id');
    }

    public function selfServiceStoreCustomer(): BelongsTo
    {
        return $this->storeCustomer();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SelfServiceCartItem::class, 'self_service_cart_id')
            ->orderBy('id');
    }
}
