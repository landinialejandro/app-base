<?php

// FILE: app/Support/Inventory/DocumentInventoryOperationService.php | V2

namespace App\Support\Inventory;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Support\Catalogs\DocumentCatalog;
use InvalidArgumentException;

class DocumentInventoryOperationService
{
    public function executeLine(
        Document $document,
        DocumentItem $item,
        float|int|string|null $quantity = null,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): array {
        $document->loadMissing('items.product');
        $item->loadMissing(['product', 'document']);

        $this->validateDocumentLine($document, $item);

        $direction = DocumentCatalog::stockDirection($document->group, $document->kind);

        if ($direction === null) {
            throw new InvalidArgumentException('El documento no impacta stock.');
        }

        $movementKind = match ($direction) {
            'in' => InventoryMovementService::KIND_INGRESAR,
            'out' => InventoryMovementService::KIND_ENTREGAR,
            default => null,
        };

        if ($movementKind === null) {
            throw new InvalidArgumentException('La dirección de stock del documento no es válida.');
        }

        $movementQuantity = $quantity !== null
            ? round((float) $quantity, 2)
            : round((float) $item->quantity, 2);

        if ($movementQuantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $pendingQuantity = app(DocumentItemStatusService::class)->pendingQuantity($item);

        if ($pendingQuantity <= 0) {
            throw new InvalidArgumentException('La línea ya no tiene cantidad pendiente.');
        }

        if ($movementQuantity > $pendingQuantity) {
            throw new InvalidArgumentException('La cantidad supera el pendiente de la línea.');
        }

        $result = app(InventoryOperationService::class)->run(
            tenantId: $document->tenant_id,
            operationType: InventoryOperationCatalog::TYPE_DOCUMENT_MOVEMENT,
            originType: InventoryOriginCatalog::TYPE_DOCUMENT,
            originId: $document->id,
            originLineType: InventoryOriginCatalog::LINE_TYPE_DOCUMENT_ITEM,
            originLineId: $item->id,
            notes: $notes,
            createdBy: $createdBy,
            callback: function ($operation) use ($document, $item, $movementKind, $movementQuantity, $notes, $createdBy) {
                return app(InventoryMovementService::class)->createForDocumentItem(
                    document: $document,
                    item: $item,
                    product: $item->product,
                    kind: $movementKind,
                    quantity: $movementQuantity,
                    notes: $notes,
                    createdBy: $createdBy,
                    operation: $operation,
                );
            },
        );

        app(DocumentItemStatusService::class)->recalculate($item->fresh());

        return $result;
    }

    public function returnLineQuantity(
        Document $document,
        DocumentItem $item,
        float|int|string|null $quantity = null,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): array {
        $document->loadMissing('items.product');
        $item->loadMissing(['product', 'document']);

        $this->validateDocumentLine($document, $item);

        $direction = DocumentCatalog::stockDirection($document->group, $document->kind);

        if ($direction === null) {
            throw new InvalidArgumentException('El documento no impacta stock.');
        }

        $movementKind = match ($direction) {
            'in' => InventoryMovementService::KIND_ENTREGAR,
            'out' => InventoryMovementService::KIND_INGRESAR,
            default => null,
        };

        if ($movementKind === null) {
            throw new InvalidArgumentException('La dirección de stock del documento no es válida.');
        }

        $movementQuantity = round((float) ($quantity ?? 0), 2);

        if ($movementQuantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $executedQuantity = app(DocumentItemStatusService::class)->executedQuantity($item);

        if ($executedQuantity <= 0) {
            throw new InvalidArgumentException('La línea no tiene cantidad ejecutada para revertir.');
        }

        if ($movementQuantity > $executedQuantity) {
            throw new InvalidArgumentException('La cantidad supera lo ejecutado de la línea.');
        }

        $result = app(InventoryOperationService::class)->run(
            tenantId: $document->tenant_id,
            operationType: InventoryOperationCatalog::TYPE_DOCUMENT_MOVEMENT,
            originType: InventoryOriginCatalog::TYPE_DOCUMENT,
            originId: $document->id,
            originLineType: InventoryOriginCatalog::LINE_TYPE_DOCUMENT_ITEM,
            originLineId: $item->id,
            notes: $notes,
            createdBy: $createdBy,
            callback: function ($operation) use ($document, $item, $movementKind, $movementQuantity, $notes, $createdBy) {
                return app(InventoryMovementService::class)->createForDocumentItem(
                    document: $document,
                    item: $item,
                    product: $item->product,
                    kind: $movementKind,
                    quantity: $movementQuantity,
                    notes: $notes,
                    createdBy: $createdBy,
                    operation: $operation,
                );
            },
        );

        app(DocumentItemStatusService::class)->recalculate($item->fresh());

        return $result;
    }

    protected function validateDocumentLine(Document $document, DocumentItem $item): void
    {
        if ((int) $item->document_id !== (int) $document->id) {
            throw new InvalidArgumentException('La línea no pertenece al documento indicado.');
        }

        if ((string) $item->tenant_id !== (string) $document->tenant_id) {
            throw new InvalidArgumentException('La línea pertenece a otro tenant.');
        }

        if (! $item->product) {
            throw new InvalidArgumentException('La línea no tiene producto asociado.');
        }

        if ((string) $item->product->tenant_id !== (string) $document->tenant_id) {
            throw new InvalidArgumentException('El producto pertenece a otro tenant.');
        }
    }
}