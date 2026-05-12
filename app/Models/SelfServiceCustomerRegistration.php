<?php

// FILE: app/Models/SelfServiceCustomerRegistration.php | V3

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SelfServiceCustomerRegistration extends Model
{
    use HasFactory;
    use TenantScoped;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'party_id',
        'self_service_customer_account_id',
        'status',
        'token',
        'name',
        'display_name',
        'document_type',
        'document_number',
        'email',
        'phone',
        'confirmed_at',
        'expires_at',
        'accepted_ip',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SelfServiceCustomerAccount::class, 'self_service_customer_account_id');
    }

    public function storeCustomer(): HasOne
    {
        return $this->hasOne(SelfServiceStoreCustomer::class, 'party_id', 'party_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}