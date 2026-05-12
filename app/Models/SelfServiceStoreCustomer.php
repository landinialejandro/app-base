<?php

// FILE: app/Models/SelfServiceStoreCustomer.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfServiceStoreCustomer extends Model
{
    use HasFactory;
    use TenantScoped;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_CANCELLED = 'cancelled';

    public const IDENTITY_STAGE_EMAIL_CONFIRMED = 'email_confirmed';
    public const IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED = 'operational_identity_completed';

    protected $fillable = [
        'self_service_customer_account_id',
        'tenant_id',
        'party_id',
        'status',
        'identity_stage',
        'operation_enabled',
        'identity_completed_at',
        'terms_accepted_at',
        'meta',
    ];

    protected $casts = [
        'operation_enabled' => 'boolean',
        'identity_completed_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SelfServiceCustomerAccount::class, 'self_service_customer_account_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canOperate(): bool
    {
        return $this->isActive() && $this->operation_enabled === true;
    }
}