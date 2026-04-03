<?php

// FILE: app/Support/Inventory/ProductStockCalculator.php | V1

namespace App\Support\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;

class ProductStockCalculator
{
    public function forProduct(Product|int $product): float
    {
        $productId = $product instanceof Product ? $product->id : $product;

        $stock = InventoryMovement::query()
            ->where('product_id', $productId)
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN kind = 'ingresar' THEN quantity
                        WHEN kind IN ('consumir', 'entregar') THEN -quantity
                        ELSE 0
                    END
                ), 0) as stock
            ")
            ->value('stock');

        return (float) $stock;
    }

    public function forProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        return InventoryMovement::query()
            ->whereIn('product_id', $productIds)
            ->selectRaw("
                product_id,
                COALESCE(SUM(
                    CASE
                        WHEN kind = 'ingresar' THEN quantity
                        WHEN kind IN ('consumir', 'entregar') THEN -quantity
                        ELSE 0
                    END
                ), 0) as stock
            ")
            ->groupBy('product_id')
            ->pluck('stock', 'product_id')
            ->map(fn ($value) => (float) $value)
            ->toArray();
    }
}
