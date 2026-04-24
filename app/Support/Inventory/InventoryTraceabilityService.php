<?php

// FILE: app/Support/Inventory/InventoryTraceabilityService.php | V1

namespace App\Support\Inventory;

use App\Models\InventoryMovement;

class InventoryTraceabilityService
{
    public function hasMovementsForOrigin(
        string $tenantId,
        string $originType,
        int|string $originId,
    ): bool {
        return InventoryMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('origin_type', $originType)
            ->where('origin_id', $originId)
            ->exists();
    }

    public function hasMovementsForOriginLine(
        string $tenantId,
        string $originLineType,
        int|string $originLineId,
    ): bool {
        return InventoryMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('origin_line_type', $originLineType)
            ->where('origin_line_id', $originLineId)
            ->exists();
    }
}