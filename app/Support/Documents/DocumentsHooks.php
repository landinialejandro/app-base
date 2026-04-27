<?php

// FILE: app/Support/Documents/DocumentsHooks.php | V1

namespace App\Support\Documents;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Support\Inventory\DocumentItemStatusService;
use App\Support\Inventory\InventoryDocumentItemHooks;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class DocumentsHooks
{
    public function beforeDocumentItemEdit(Document $document, DocumentItem $item): void
    {
        try {
            $this->inventoryHooks()->beforeUpdate($document, $item, []);
        } catch (InvalidArgumentException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('DocumentsHooks.beforeDocumentItemEdit falló y no interrumpió el flujo principal.', [
                'document_id' => $document->id,
                'document_item_id' => $item->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function beforeDocumentItemDestroy(Document $document, DocumentItem $item): void
    {
        try {
            $this->inventoryHooks()->beforeDelete($document, $item);
        } catch (InvalidArgumentException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('DocumentsHooks.beforeDocumentItemDestroy falló y no interrumpió el flujo principal.', [
                'document_id' => $document->id,
                'document_item_id' => $item->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function beforeDocumentItemUpdate(Document $document, DocumentItem $item, array $data): array
    {
        try {
            $this->inventoryHooks()->beforeUpdate($document, $item, $data);

            return $data;
        } catch (InvalidArgumentException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('DocumentsHooks.beforeDocumentItemUpdate falló y se aplicó fallback al dataset original.', [
                'document_id' => $document->id,
                'document_item_id' => $item->id,
                'message' => $e->getMessage(),
            ]);

            return $data;
        }
    }

    public function afterDocumentItemUpdate(Document $document, DocumentItem $item): void
    {
        try {
            app(DocumentItemStatusService::class)->recalculate($item);
        } catch (Throwable $e) {
            Log::warning('DocumentsHooks.afterDocumentItemUpdate falló y no interrumpió el flujo principal.', [
                'document_id' => $document->id,
                'document_item_id' => $item->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function inventoryHooks(): InventoryDocumentItemHooks
    {
        return app(InventoryDocumentItemHooks::class);
    }
}