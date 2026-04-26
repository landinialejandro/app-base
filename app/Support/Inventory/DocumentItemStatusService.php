<?php

// FILE: app/Support/Inventory/DocumentItemStatusService.php | V3

namespace App\Support\Inventory;

use App\Models\DocumentItem;
use App\Models\InventoryMovement;
use App\Support\Catalogs\DocumentCatalog;
use App\Support\Catalogs\DocumentItemCatalog;
use App\Support\LineItems\LineItemMath;

class DocumentItemStatusService
{
    public function recalculate(DocumentItem $item): string
    {
        $math = app(LineItemMath::class);

        $status = $math->statusFor(
            quantity: $item->quantity,
            executedQuantity: $this->executedQuantity($item),
            pendingStatus: DocumentItemCatalog::STATUS_PENDING,
            partialStatus: DocumentItemCatalog::STATUS_PARTIAL,
            completedStatus: DocumentItemCatalog::STATUS_COMPLETED,
            cancelledStatus: DocumentItemCatalog::STATUS_CANCELLED,
            cancelled: $this->isCancelled($item),
        );

        return $this->persistStatus($item, $status);
    }

    public function recalculateMany(iterable $items): void
    {
        foreach ($items as $item) {
            if (! $item instanceof DocumentItem) {
                continue;
            }

            $this->recalculate($item);
        }
    }

    public function executedQuantity(DocumentItem $item): float
    {
        $math = app(LineItemMath::class);

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

        return max(0, $math->normalizeQuantity($executed - $returned));
    }

    public function pendingQuantity(DocumentItem $item): float
    {
        return app(LineItemMath::class)->pendingQuantity(
            $item->quantity,
            $this->executedQuantity($item),
        );
    }

    public function hasMovements(DocumentItem $item): bool
    {
        return InventoryMovement::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('origin_line_type', InventoryOriginCatalog::LINE_TYPE_DOCUMENT_ITEM)
            ->where('origin_line_id', $item->id)
            ->exists();
    }

    protected function persistStatus(DocumentItem $item, string $status): string
    {
        if ($item->status !== $status) {
            $item->forceFill([
                'status' => $status,
            ])->saveQuietly();
        }

        return $status;
    }

    protected function isCancelled(DocumentItem $item): bool
    {
        return $item->status === DocumentItemCatalog::STATUS_CANCELLED;
    }
}