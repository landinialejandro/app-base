<?php

// FILE: app/Support/LineItems/LineItemViewHelper.php | V1

namespace App\Support\LineItems;

final class LineItemViewHelper
{
    public function statusValue(object $item, string $catalogClass): string
    {
        return (string) ($item->status ?: $catalogClass::STATUS_PENDING);
    }

    public function statusLabel(object $item, string $catalogClass): string
    {
        return $catalogClass::statusLabel(
            $this->statusValue($item, $catalogClass)
        );
    }

    public function statusBadge(object $item, string $catalogClass): string
    {
        return $catalogClass::badgeClass(
            $this->statusValue($item, $catalogClass)
        );
    }

    public function isFinal(object $item, string $catalogClass): bool
    {
        return $catalogClass::isFinal(
            $this->statusValue($item, $catalogClass)
        );
    }

    public function canEdit(object $item, string $catalogClass, bool $parentReadonly = false): bool
    {
        return ! $parentReadonly && ! $this->isFinal($item, $catalogClass);
    }

    public function canDelete(object $item, string $catalogClass, bool $parentReadonly = false): bool
    {
        return ! $parentReadonly && ! $this->isFinal($item, $catalogClass);
    }

    public function qty(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 2, ',', '.');
    }

    public function qtyInput(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }

    public function money(float|int|string|null $value): string
    {
        return '$'.$this->qty($value);
    }

    public function formState(object $item, string $catalogClass, float|int|string|null $executedQuantity = 0): array
    {
        return [
            'itemExists' => (bool) ($item->exists ?? false),
            'executedQuantity' => (float) ($executedQuantity ?? 0),
            'lineStatusLabel' => $this->statusLabel($item, $catalogClass),
            'lineStatusBadge' => $this->statusBadge($item, $catalogClass),
        ];
    }
}