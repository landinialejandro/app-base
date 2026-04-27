<?php

// FILE: app/Support/LineItems/LineItemTotals.php | V1

namespace App\Support\LineItems;

use Illuminate\Support\Collection;

final class LineItemTotals
{
    public function executedTotal(iterable $items, callable $executedQuantityResolver): float
    {
        $math = app(LineItemMath::class);

        return $math->normalizeMoney(
            Collection::make($items)->sum(function ($item) use ($executedQuantityResolver, $math) {
                $executedQuantity = $executedQuantityResolver($item);

                return $math->lineTotal(
                    $executedQuantity,
                    $item->unit_price ?? 0,
                );
            })
        );
    }
}