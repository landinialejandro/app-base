<?php
// app/Models/Invitation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model {
    protected $fillable = [
        'email',
        'organization_id',
        'token',
        'role',
        'expires_at',
        'accepted'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted' => 'boolean',
    ];

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function isValid(): bool {
        return !$this->accepted && !$this->isExpired();
    }

    public function isExpired(): bool {
        return $this->expires_at->isPast();
    }

    public static function generateToken(): string {
        return Str::random(60);
    }
}
