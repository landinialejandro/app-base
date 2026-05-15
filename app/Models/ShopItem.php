<?php

// FILE: app/Models/ShopItem.php | V1

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class ShopItem extends Model
{
    use SoftDeletes;
    use TenantScoped;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';

    protected $table = 'self_service_shop_items';

    protected $fillable = [
        'tenant_id',
        'self_service_shop_id',
        'product_id',
        'is_visible',
        'use_product_price',
        'price',
        'display_name',
        'display_description',
        'sort_order',
        'status',
        'meta',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'use_product_price' => 'boolean',
        'price' => 'decimal:2',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (ShopItem $item): void {
            $item->normalizePublicationState();

            if (! $item->self_service_shop_id || ! $item->tenant_id) {
                throw new InvalidArgumentException('El artículo publicado debe pertenecer a una tienda.');
            }

            $shopBelongsToTenant = Shop::query()
                ->whereKey($item->self_service_shop_id)
                ->where('tenant_id', $item->tenant_id)
                ->exists();

            if (! $shopBelongsToTenant) {
                throw new InvalidArgumentException('La tienda del artículo debe pertenecer al tenant indicado.');
            }

            if (! $item->product_id) {
                return;
            }

            $productBelongsToTenant = Product::query()
                ->whereKey($item->product_id)
                ->where('tenant_id', $item->tenant_id)
                ->exists();

            if (! $productBelongsToTenant) {
                throw new InvalidArgumentException('El producto publicado debe pertenecer al tenant de la tienda.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'self_service_shop_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function usesProductPrice(): bool
    {
        return $this->use_product_price === true;
    }

    public function isVisible(): bool
    {
        return $this->is_visible === true;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function displayName(): string
    {
        return filled($this->display_name)
            ? (string) $this->display_name
            : (string) ($this->product?->name ?? 'Producto');
    }

    public function displayDescription(): ?string
    {
        if (filled($this->display_description)) {
            return (string) $this->display_description;
        }

        return $this->product?->description;
    }

    public function displayPrice(): mixed
    {
        if ($this->usesProductPrice()) {
            return $this->product?->price;
        }

        return $this->price;
    }

    public function canBeShownInShop(): bool
    {
        return $this->isPublished()
            && $this->isVisible()
            && $this->shop !== null
            && $this->shop->isActive()
            && $this->shop->tenant_id === $this->tenant_id
            && $this->product !== null
            && $this->product->tenant_id === $this->tenant_id
            && $this->product->is_active === true;
    }

    public function normalizePublicationState(): void
    {
        if ($this->status === self::STATUS_DRAFT || $this->status === self::STATUS_HIDDEN) {
            $this->is_visible = false;

            return;
        }

        if ($this->is_visible === true) {
            $this->status = self::STATUS_PUBLISHED;
        }
    }
}