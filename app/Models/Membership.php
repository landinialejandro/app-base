<?php

// FILE: app/Models/Membership.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Membership extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'is_owner',
        'joined_at',
        'blocked_at',
        'blocked_reason',
    ];

    protected $casts = [
        'is_owner' => 'boolean',
        'joined_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'membership_role')
            ->withPivot('branch_id')
            ->withTimestamps();
    }
}