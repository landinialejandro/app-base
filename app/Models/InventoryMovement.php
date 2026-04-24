<?php

// FILE: app/Models/InventoryMovement.php | V3

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    use ResolvesTenantRouteBinding;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'origin_type',
        'origin_id',
        'origin_line_type',
        'origin_line_id',
        'kind',
        'quantity',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isIngreso(): bool
    {
        return $this->kind === 'ingresar';
    }

    public function isConsumo(): bool
    {
        return $this->kind === 'consumir';
    }

    public function isEntrega(): bool
    {
        return $this->kind === 'entregar';
    }

    public function affectsStockAsPositive(): bool
    {
        return $this->isIngreso();
    }

    public function affectsStockAsNegative(): bool
    {
        return $this->isConsumo() || $this->isEntrega();
    }

    public function signedQuantity(): float
    {
        $quantity = (float) $this->quantity;

        if ($this->affectsStockAsPositive()) {
            return $quantity;
        }

        if ($this->affectsStockAsNegative()) {
            return -1 * $quantity;
        }

        return 0.0;
    }

    public function isFromOrigin(string $originType, int|string|null $originId): bool
    {
        return (string) $this->origin_type === $originType
            && (string) $this->origin_id === (string) $originId;
    }

    public function isFromOriginLine(string $originLineType, int|string|null $originLineId): bool
    {
        return (string) $this->origin_line_type === $originLineType
            && (string) $this->origin_line_id === (string) $originLineId;
    }
}