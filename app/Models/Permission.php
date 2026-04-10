<?php

// FILE: app/Models/Permission.php | V3

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'group',
        'description',
    ];

    protected $casts = [
        'description' => 'string',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withPivot(['scope', 'execution_mode', 'constraints'])
            ->withTimestamps();
    }

    public function membershipOverrides(): HasMany
    {
        return $this->hasMany(MembershipPermissionOverride::class);
    }
}
