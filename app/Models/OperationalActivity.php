<?php

// FILE: app/Models/OperationalActivity.php | V1

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OperationalActivity extends Model
{
    use ResolvesTenantRouteBinding;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'actor_user_id',
        'subject_user_id',
        'module',
        'record_type',
        'record_id',
        'activity_type',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function record(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'record_type', 'record_id');
    }
}