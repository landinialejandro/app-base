<?php

// FILE: app/Models/Shop.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use SoftDeletes;
    use TenantScoped;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'self_service_shops';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'status',
        'published_at',
        'meta',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopItem::class, 'self_service_shop_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }
}