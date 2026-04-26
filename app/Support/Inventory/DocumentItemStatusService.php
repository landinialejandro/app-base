<?php

// FILE: app/Support/Inventory/DocumentItemStatusService.php | V1

namespace App\Support\Inventory;

use App\Models\DocumentItem;
use App\Models\InventoryMovement;
use App\Support\Catalogs\DocumentCatalog;

class DocumentItemStatusService
{

public function executedQuantity(DocumentItem $item): float
{
    $item->loadMissing('document');

    $document = $item->document;

    if (! $document) {
        return 0.0;
    }

    $direction = DocumentCatalog::stockDirection($document->group, $document->kind);

    $executeKind = match ($direction) {
        'in' => InventoryMovementService::KIND_INGRESAR,
        'out' => InventoryMovementService::KIND_ENTREGAR,
        default => null,
    };

    $reverseKind = match ($direction) {
        'in' => InventoryMovementService::KIND_ENTREGAR,
        'out' => InventoryMovementService::KIND_INGRESAR,
        default => null,
    };

    if ($executeKind === null || $reverseKind === null) {
        return 0.0;
    }

    $executed = InventoryMovement::query()
        ->where('tenant_id', $item->tenant_id)
        ->where('origin_line_type', InventoryOriginCatalog::LINE_TYPE_DOCUMENT_ITEM)
        ->where('origin_line_id', $item->id)
        ->where('kind', $executeKind)
        ->sum('quantity');

    $returned = InventoryMovement::query()
        ->where('tenant_id', $item->tenant_id)
        ->where('origin_line_type', InventoryOriginCatalog::LINE_TYPE_DOCUMENT_ITEM)
        ->where('origin_line_id', $item->id)
        ->where('kind', $reverseKind)
        ->sum('quantity');

    return max(0, $this->normalizeQuantity($executed - $returned));
}

    public function pendingQuantity(DocumentItem $item): float
    {
        $orderedQuantity = $this->normalizeQuantity($item->quantity);
        $executedQuantity = $this->executedQuantity($item);

        return max(0, $this->normalizeQuantity($orderedQuantity - $executedQuantity));
    }

    public function hasMovements(DocumentItem $item): bool
    {
        return InventoryMovement::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('origin_line_type', InventoryOriginCatalog::LINE_TYPE_DOCUMENT_ITEM)
            ->where('origin_line_id', $item->id)
            ->exists();
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}