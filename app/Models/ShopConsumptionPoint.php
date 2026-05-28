<?php

// FILE: app/Models/ShopConsumptionPoint.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ShopConsumptionPoint extends Model
{
    use SoftDeletes;
    use TenantScoped;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'self_service_shop_consumption_points';

    protected $fillable = [
        'tenant_id',
        'self_service_shop_id',
        'name',
        'code',
        'description',
        'status',
        'public_token',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (ShopConsumptionPoint $point): void {
            if (! $point->tenant_id || ! $point->self_service_shop_id) {
                throw new InvalidArgumentException('El punto de consumo debe pertenecer a una tienda.');
            }

            $shopBelongsToTenant = Shop::query()
                ->whereKey($point->self_service_shop_id)
                ->where('tenant_id', $point->tenant_id)
                ->exists();

            if (! $shopBelongsToTenant) {
                throw new InvalidArgumentException('La tienda del punto de consumo debe pertenecer al tenant indicado.');
            }

            if (! filled($point->status)) {
                $point->status = self::STATUS_ACTIVE;
            }

            if ($point->sort_order === null) {
                $point->sort_order = 0;
            }

            if (! filled($point->public_token)) {
                do {
                    $token = Str::random(40);
                } while (self::query()->where('public_token', $token)->exists());

                $point->public_token = $token;
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'self_service_shop_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function displayName(): string
    {
        return filled($this->name) ? (string) $this->name : 'Punto de consumo';
    }
}
