<?php

// FILE: app/Models/MembershipPermissionOverride.php | V2

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPermissionOverride extends Model
{
    protected $fillable = [
        'membership_id',
        'permission_id',
        'is_allowed',
        'scope',
        'execution_mode',
        'constraints',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'constraints' => 'array',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
