<?php

// FILE: app/Support/Inventory/InventoryDocumentItemHooks.php | V2

namespace App\Support\Inventory;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Support\Catalogs\DocumentItemCatalog;
use InvalidArgumentException;

class InventoryDocumentItemHooks
{
    public function beforeUpdate(Document $document, DocumentItem $item, array $data): void
    {
        $this->validateBelongsToDocument($document, $item);

        if (DocumentItemCatalog::isFinal($item->status)) {
            throw new InvalidArgumentException(
                'No se puede editar una línea documental en estado final.'
            );
        }

        if (! array_key_exists('quantity', $data)) {
            return;
        }

        $executedQuantity = app(DocumentItemStatusService::class)->executedQuantity($item);
        $incomingQuantity = $this->normalizeQuantity($data['quantity']);

        if ($executedQuantity > 0 && $incomingQuantity < $executedQuantity) {
            throw new InvalidArgumentException(
                'No se puede reducir la cantidad de la línea por debajo de lo ya ejecutado en inventario.'
            );
        }
    }

    public function beforeDelete(Document $document, DocumentItem $item): void
    {
        $this->validateBelongsToDocument($document, $item);

        if (DocumentItemCatalog::isFinal($item->status)) {
            throw new InvalidArgumentException(
                'No se puede eliminar una línea documental en estado final.'
            );
        }

        if (app(DocumentItemStatusService::class)->hasMovements($item)) {
            throw new InvalidArgumentException(
                'No se puede eliminar una línea documental con movimientos de inventario asociados.'
            );
        }
    }

    protected function validateBelongsToDocument(Document $document, DocumentItem $item): void
    {
        if ((int) $item->document_id !== (int) $document->id) {
            throw new InvalidArgumentException('La línea no pertenece al documento indicado.');
        }

        if ((string) $item->tenant_id !== (string) $document->tenant_id) {
            throw new InvalidArgumentException('La línea pertenece a otro tenant.');
        }
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}