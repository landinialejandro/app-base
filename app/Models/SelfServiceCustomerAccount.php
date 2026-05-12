<?php

// FILE: app/Models/SelfServiceCustomerAccount.php | V2

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SelfServiceCustomerAccount extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'email',
        'display_name',
        'phone',
        'password_hash',
        'password_set_at',
        'password_needs_reset',
        'access_enabled',
        'status',
        'email_confirmed_at',
        'last_access_at',
        'meta',
    ];

    protected $casts = [
        'password_set_at' => 'datetime',
        'password_needs_reset' => 'boolean',
        'access_enabled' => 'boolean',
        'email_confirmed_at' => 'datetime',
        'last_access_at' => 'datetime',
        'meta' => 'array',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function storeCustomers(): HasMany
    {
        return $this->hasMany(SelfServiceStoreCustomer::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasExternalCredential(): bool
    {
        return filled($this->password_hash) && $this->password_set_at !== null;
    }

    public function canAccessExternally(): bool
    {
        return $this->isActive()
            && $this->access_enabled === true
            && $this->hasExternalCredential()
            && $this->password_needs_reset === false;
    }
}