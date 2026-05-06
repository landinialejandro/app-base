<?php

// FILE: app/Models/Order.php | V10

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
        'counterparty_name',
        'asset_id',
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

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
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

    public function displayCounterpartyName(): string
    {
        return (string) ($this->counterparty_name ?: $this->party?->name ?: '—');
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