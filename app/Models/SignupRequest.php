<?php

// FILE: app/Models/SignupRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignupRequest extends Model
{
    protected $fillable = [
        'requested_name',
        'requested_email',
        'company_name',
        'phone_whatsapp',
        'status',
        'review_notes',
        'approved_at',
        'rejected_at',
        'approved_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}