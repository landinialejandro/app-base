<?php

// FILE: app/Models/Invitation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'status',
        'email',
        'token',
        'signup_request_id',
        'invited_by_user_id',
        'expires_at',
        'sent_at',
        'accepted_at',
        'accepted_ip',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function signupRequest(): BelongsTo
    {
        return $this->belongsTo(SignupRequest::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}