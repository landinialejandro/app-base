<?php

// FILE: app/Support/Inventory/InventoryShopStockAvailabilityService.php | V1

namespace App\Support\Inventory;

use App\Models\ShopItem;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InventoryShopStockAvailabilityService
{
    public const MESSAGE_STOCK_NOT_AVAILABLE = 'Este producto no está disponible en la cantidad solicitada. Modificá el carrito para continuar.';

    public function __construct(
        protected ProductStockCalculator $stockCalculator,
    ) {
    }

    public function availabilityFor(ShopItem $shopItem, array $commercialPolicy): array
    {
        $currentStock = $this->stockCalculator->forProduct($shopItem->product_id);
        $protectedQuantity = $this->nullableFloat($commercialPolicy['stock_protected_quantity'] ?? null) ?? 0.0;
        $targetQuantity = $this->nullableFloat($commercialPolicy['stock_target_quantity'] ?? null);
        $allowTargetMargin = ($commercialPolicy['allow_target_margin'] ?? false) === true;
        $shopStockLimit = $this->nullableFloat($commercialPolicy['shop_stock_limit'] ?? null);

        $floor = $this->floor(
            protectedQuantity: $protectedQuantity,
            targetQuantity: $targetQuantity,
            allowTargetMargin: $allowTargetMargin,
        );

        $availableByStock = max(0.0, $currentStock - $floor);
        $availableQuantity = $shopStockLimit !== null
            ? min($availableByStock, $shopStockLimit)
            : $availableByStock;

        return [
            'enabled' => ($commercialPolicy['effective_stock_control_enabled'] ?? false) === true,
            'product_id' => $shopItem->product_id,
            'current_stock' => $currentStock,
            'protected_quantity' => $protectedQuantity,
            'target_quantity' => $targetQuantity,
            'allow_target_margin' => $allowTargetMargin,
            'shop_stock_limit' => $shopStockLimit,
            'available_quantity' => $availableQuantity,
        ];
    }

    public function ensureAvailable(
        ShopItem $shopItem,
        array $commercialPolicy,
        float|int|string $requestedQuantity,
    ): void {
        if (($commercialPolicy['effective_stock_control_enabled'] ?? false) !== true) {
            return;
        }

        $availability = $this->availabilityFor($shopItem, $commercialPolicy);

        if ((float) $requestedQuantity > (float) $availability['available_quantity']) {
            throw new HttpException(422, self::MESSAGE_STOCK_NOT_AVAILABLE);
        }
    }

    private function floor(float $protectedQuantity, ?float $targetQuantity, bool $allowTargetMargin): float
    {
        if ($allowTargetMargin) {
            return $protectedQuantity;
        }

        if ($targetQuantity !== null) {
            return max($protectedQuantity, $targetQuantity);
        }

        return $protectedQuantity;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
