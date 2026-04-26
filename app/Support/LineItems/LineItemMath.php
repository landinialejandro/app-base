<?php

// FILE: app/Support/LineItems/LineItemMath.php | V1

namespace App\Support\LineItems;

final class LineItemMath
{
    public function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    public function lineTotal(
        float|int|string|null $quantity,
        float|int|string|null $unitPrice,
    ): float {
        return $this->normalizeMoney(
            $this->normalizeQuantity($quantity) * $this->normalizeMoney($unitPrice)
        );
    }

    public function pendingQuantity(
        float|int|string|null $quantity,
        float|int|string|null $executedQuantity,
    ): float {
        return max(
            0,
            $this->normalizeQuantity(
                $this->normalizeQuantity($quantity) - $this->normalizeQuantity($executedQuantity)
            )
        );
    }

    public function statusFor(
        float|int|string|null $quantity,
        float|int|string|null $executedQuantity,
        string $pendingStatus,
        string $partialStatus,
        string $completedStatus,
        ?string $cancelledStatus = null,
        bool $cancelled = false,
    ): string {
        if ($cancelled && $cancelledStatus !== null) {
            return $cancelledStatus;
        }

        $orderedQuantity = $this->normalizeQuantity($quantity);
        $executedQuantity = $this->normalizeQuantity($executedQuantity);

        if ($executedQuantity <= 0) {
            return $pendingStatus;
        }

        if ($executedQuantity < $orderedQuantity) {
            return $partialStatus;
        }

        return $completedStatus;
    }

    public function normalizeMoney(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}