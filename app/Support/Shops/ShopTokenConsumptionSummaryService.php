<?php

// FILE: app/Support/Shops/ShopTokenConsumptionSummaryService.php | V1

namespace App\Support\Shops;

use App\Models\SelfServiceTokenConsumptionAttempt;
use App\Models\SelfServiceTokenPocket;
use App\Models\SelfServiceTokenPocketMovement;
use App\Models\Shop;
use App\Models\ShopItem;
use Illuminate\Support\Collection;

class ShopTokenConsumptionSummaryService
{
    public function forShop(Shop $shop): array
    {
        $productIds = ShopItem::query()
            ->where('tenant_id', $shop->tenant_id)
            ->where('self_service_shop_id', $shop->id)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return $this->emptySummary();
        }

        $pockets = SelfServiceTokenPocket::query()
            ->with(['account', 'storeCustomer.party', 'product'])
            ->where('tenant_id', $shop->tenant_id)
            ->whereIn('product_id', $productIds)
            ->where('status', SelfServiceTokenPocket::STATUS_ACTIVE)
            ->orderBy('product_id')
            ->orderBy('id')
            ->get();

        $attempts = SelfServiceTokenConsumptionAttempt::query()
            ->with(['account', 'storeCustomer.party', 'product', 'pocket'])
            ->where('tenant_id', $shop->tenant_id)
            ->whereIn('product_id', $productIds)
            ->latest('id')
            ->limit(20)
            ->get();

        $movements = SelfServiceTokenPocketMovement::query()
            ->with(['pocket.account', 'pocket.storeCustomer.party', 'product'])
            ->where('tenant_id', $shop->tenant_id)
            ->whereIn('product_id', $productIds)
            ->latest('id')
            ->limit(20)
            ->get();

        return [
            'pockets' => $pockets->map(fn (SelfServiceTokenPocket $pocket): array => $this->presentPocket($pocket))->values(),
            'attempts' => $attempts->map(fn (SelfServiceTokenConsumptionAttempt $attempt): array => $this->presentAttempt($attempt))->values(),
            'movements' => $movements->map(fn (SelfServiceTokenPocketMovement $movement): array => $this->presentMovement($movement))->values(),
            'metrics' => [
                'pockets_count' => $pockets->count(),
                'available_quantity' => $this->normalizeNumber((float) $pockets->sum('quantity_available')),
                'pending_attempts_count' => SelfServiceTokenConsumptionAttempt::query()
                    ->where('tenant_id', $shop->tenant_id)
                    ->whereIn('product_id', $productIds)
                    ->where('status', SelfServiceTokenConsumptionAttempt::STATUS_PENDING)
                    ->count(),
                'confirmed_attempts_count' => SelfServiceTokenConsumptionAttempt::query()
                    ->where('tenant_id', $shop->tenant_id)
                    ->whereIn('product_id', $productIds)
                    ->where('status', SelfServiceTokenConsumptionAttempt::STATUS_CONFIRMED)
                    ->count(),
                'purchase_credit_quantity' => $this->movementQuantity(
                    tenantId: (string) $shop->tenant_id,
                    productIds: $productIds,
                    movementType: SelfServiceTokenPocketMovement::TYPE_PURCHASE_CREDIT,
                ),
                'consumption_quantity' => $this->movementQuantity(
                    tenantId: (string) $shop->tenant_id,
                    productIds: $productIds,
                    movementType: SelfServiceTokenPocketMovement::TYPE_CONSUMPTION,
                ),
            ],
        ];
    }

    private function emptySummary(): array
    {
        return [
            'pockets' => collect(),
            'attempts' => collect(),
            'movements' => collect(),
            'metrics' => [
                'pockets_count' => 0,
                'available_quantity' => 0,
                'pending_attempts_count' => 0,
                'confirmed_attempts_count' => 0,
                'purchase_credit_quantity' => 0,
                'consumption_quantity' => 0,
            ],
        ];
    }

    private function presentPocket(SelfServiceTokenPocket $pocket): array
    {
        $quantity = $this->normalizeNumber((float) $pocket->quantity_available);
        $totalMinutes = $pocket->unit_seconds_snapshot !== null
            ? $this->normalizeNumber(((float) $pocket->quantity_available * (int) $pocket->unit_seconds_snapshot) / 60)
            : null;

        return [
            'customer_label' => $this->customerLabel($pocket),
            'product_name' => $pocket->product?->name ?: 'Ficha',
            'sku' => $pocket->product?->sku,
            'quantity_available' => $quantity,
            'unit_seconds_snapshot' => $pocket->unit_seconds_snapshot,
            'total_minutes' => $totalMinutes,
            'status' => $pocket->status,
        ];
    }

    private function presentAttempt(SelfServiceTokenConsumptionAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'customer_label' => $this->customerLabel($attempt),
            'product_name' => $attempt->product?->name ?: 'Ficha',
            'quantity' => $this->normalizeNumber((float) $attempt->quantity),
            'total_seconds' => $attempt->total_seconds,
            'total_minutes' => $attempt->total_seconds !== null
                ? $this->normalizeNumber((float) $attempt->total_seconds / 60)
                : null,
            'status' => $attempt->status,
            'confirmed_at' => $attempt->confirmed_at,
            'failed_at' => $attempt->failed_at,
        ];
    }

    private function presentMovement(SelfServiceTokenPocketMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'customer_label' => $movement->pocket ? $this->customerLabel($movement->pocket) : '—',
            'product_name' => $movement->product?->name ?: 'Ficha',
            'movement_type' => $movement->movement_type,
            'quantity' => $this->normalizeNumber((float) $movement->quantity),
            'balance_after' => $this->normalizeNumber((float) $movement->balance_after),
            'source_type' => $movement->source_type,
            'source_id' => $movement->source_id,
            'created_at' => $movement->created_at,
        ];
    }

    private function movementQuantity(string $tenantId, Collection $productIds, string $movementType): int|float
    {
        $quantity = SelfServiceTokenPocketMovement::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('product_id', $productIds)
            ->where('movement_type', $movementType)
            ->sum('quantity');

        return $this->normalizeNumber((float) $quantity);
    }

    private function customerLabel(SelfServiceTokenPocket|SelfServiceTokenConsumptionAttempt $record): string
    {
        return $record->storeCustomer?->party?->display_name
            ?: $record->storeCustomer?->party?->name
            ?: $record->account?->display_name
            ?: $record->account?->email
            ?: 'Customer externo';
    }

    private function normalizeNumber(float $value): int|float
    {
        return floor($value) === $value ? (int) $value : $value;
    }
}
