<?php

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory;
    use ResolvesTenantRouteBinding;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'party_id',
        'order_id',
        'asset_id',
        'assigned_user_id',
        'kind',
        'status',
        'work_mode',
        'title',
        'notes',
        'workstation_name',
        'scheduled_date',
        'starts_at',
        'ends_at',
        'is_all_day',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_all_day' => 'boolean',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hasTimeRange(): bool
    {
        return ! empty($this->starts_at) && ! empty($this->ends_at);
    }

    public function blocksFullDay(): bool
    {
        return (bool) $this->is_all_day;
    }
}
