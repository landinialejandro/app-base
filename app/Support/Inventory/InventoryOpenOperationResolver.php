<?php

// FILE: app/Support/Inventory/InventoryOpenOperationResolver.php | V1

namespace App\Support\Inventory;

use App\Models\InventoryOperation;
use Illuminate\Support\Facades\Cache;

class InventoryOpenOperationResolver
{
    protected const TTL_MINUTES = 5;

    public function resolve(
        string $tenantId,
        string $operationType,
        ?string $originType = null,
        int|string|null $originId = null,
        ?string $originLineType = null,
        int|string|null $originLineId = null,
        int|string|null $createdBy = null,
        ?string $notes = null,
    ): InventoryOperation {
        $key = $this->cacheKey(
            tenantId: $tenantId,
            operationType: $operationType,
            originType: $originType,
            originId: $originId,
            createdBy: $createdBy,
        );

        $operationId = Cache::get($key);

        if ($operationId) {
            $operation = InventoryOperation::query()
                ->where('tenant_id', $tenantId)
                ->where('operation_type', $operationType)
                ->where('origin_type', $originType)
                ->where('origin_id', $originId)
                ->where('created_by', $createdBy)
                ->whereKey($operationId)
                ->first();

            if ($operation) {
                Cache::put($key, $operation->id, now()->addMinutes(self::TTL_MINUTES));

                return $operation;
            }
        }

        $operation = app(InventoryOperationService::class)->create(
            tenantId: $tenantId,
            operationType: $operationType,
            originType: $originType,
            originId: $originId,
            originLineType: $originLineType,
            originLineId: $originLineId,
            notes: $notes,
            createdBy: $createdBy,
        );

        Cache::put($key, $operation->id, now()->addMinutes(self::TTL_MINUTES));

        return $operation;
    }

    protected function cacheKey(
        string $tenantId,
        string $operationType,
        ?string $originType = null,
        int|string|null $originId = null,
        int|string|null $createdBy = null,
    ): string {
        return implode(':', [
            'inventory_open_operation',
            $tenantId,
            $createdBy ?: 'system',
            $operationType,
            $originType ?: 'none',
            $originId ?: 'none',
        ]);
    }
}