<?php

// FILE: app/Support/Inventory/InventoryMovementService.php | V1

namespace App\Support\Inventory;

use App\Models\Document;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryMovementService
{
    public const KIND_INGRESAR = 'ingresar';

    public const KIND_CONSUMIR = 'consumir';

    public const KIND_ENTREGAR = 'entregar';

    public static function kinds(): array
    {
        return [
            self::KIND_INGRESAR,
            self::KIND_CONSUMIR,
            self::KIND_ENTREGAR,
        ];
    }

    public function ingresar(
        Product $product,
        float|int|string $quantity,
        ?string $notes = null,
        ?Order $order = null,
        ?Document $document = null,
        int|string|null $createdBy = null,
    ): InventoryMovement {
        return $this->createMovement(
            product: $product,
            kind: self::KIND_INGRESAR,
            quantity: $quantity,
            notes: $notes,
            order: $order,
            document: $document,
            createdBy: $createdBy,
        );
    }

    public function consumir(
        Product $product,
        float|int|string $quantity,
        ?string $notes = null,
        ?Order $order = null,
        ?Document $document = null,
        int|string|null $createdBy = null,
    ): InventoryMovement {
        return $this->createMovement(
            product: $product,
            kind: self::KIND_CONSUMIR,
            quantity: $quantity,
            notes: $notes,
            order: $order,
            document: $document,
            createdBy: $createdBy,
        );
    }

    public function entregar(
        Product $product,
        float|int|string $quantity,
        ?string $notes = null,
        ?Order $order = null,
        ?Document $document = null,
        int|string|null $createdBy = null,
    ): InventoryMovement {
        return $this->createMovement(
            product: $product,
            kind: self::KIND_ENTREGAR,
            quantity: $quantity,
            notes: $notes,
            order: $order,
            document: $document,
            createdBy: $createdBy,
        );
    }

    protected function createMovement(
        Product $product,
        string $kind,
        float|int|string $quantity,
        ?string $notes = null,
        ?Order $order = null,
        ?Document $document = null,
        int|string|null $createdBy = null,
    ): InventoryMovement {
        $normalizedQuantity = (float) $quantity;

        if (! in_array($kind, self::kinds(), true)) {
            throw new InvalidArgumentException('Tipo de movimiento inválido.');
        }

        if ($normalizedQuantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        if ($order && $order->tenant_id !== $product->tenant_id) {
            throw new InvalidArgumentException('La orden pertenece a otro tenant.');
        }

        if ($document && $document->tenant_id !== $product->tenant_id) {
            throw new InvalidArgumentException('El documento pertenece a otro tenant.');
        }

        return DB::transaction(function () use ($product, $kind, $normalizedQuantity, $notes, $order, $document, $createdBy) {
            return InventoryMovement::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'order_id' => $order?->id,
                'document_id' => $document?->id,
                'kind' => $kind,
                'quantity' => $normalizedQuantity,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }
}
