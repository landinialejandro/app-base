<?php

// FILE: app/Models/SelfServiceTokenConsumptionAttempt.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfServiceTokenConsumptionAttempt extends Model
{
    use TenantScoped;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id',
        'self_service_customer_account_id',
        'self_service_store_customer_id',
        'self_service_token_pocket_id',
        'product_id',
        'quantity',
        'unit_label_snapshot',
        'unit_seconds_snapshot',
        'total_seconds',
        'status',
        'source_type',
        'source_id',
        'idempotency_key',
        'request_payload',
        'response_payload',
        'confirmed_at',
        'failed_at',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_seconds_snapshot' => 'integer',
        'total_seconds' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
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

    public function pocket(): BelongsTo
    {
        return $this->belongsTo(SelfServiceTokenPocket::class, 'self_service_token_pocket_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
