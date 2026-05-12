<?php

// FILE: app/Models/SelfServiceStoreSelectionToken.php | V1

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfServiceStoreSelectionToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_hash',
        'self_service_customer_account_id',
        'expires_at',
        'used_at',
        'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'meta' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SelfServiceCustomerAccount::class, 'self_service_customer_account_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isAvailable(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed();
    }
}