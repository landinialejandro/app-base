<?php

// FILE: app/Models/Order.php | V7

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use ResolvesTenantRouteBinding;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'party_id',
        'asset_id',
        'task_id',
        'group',
        'kind',
        'number',
        'sequence_prefix',
        'point_of_sale',
        'sequence_number',
        'status',
        'ordered_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ordered_at' => 'date',
        'sequence_number' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Order $order): void {
            if ($order->isForceDeleting()) {
                return;
            }

            if ($order->task_id === null) {
                return;
            }

            $order->forceFill([
                'task_id' => null,
            ])->saveQuietly();
        });
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function calculateTotal(): float
    {
        return (float) $this->items->sum('subtotal');
    }

    public function getTotalAttribute(): float
    {
        return $this->calculateTotal();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->orderBy('sort_order')
            ->latest('id');
    }

    public function hasClosedItems(): bool
    {
        if ($this->relationLoaded('items')) {
            return $this->items
                ->contains(fn ($item) => in_array($item->status, ['completed', 'cancelled'], true));
        }

        return $this->items()
            ->whereIn('status', ['completed', 'cancelled'])
            ->exists();
    }
}