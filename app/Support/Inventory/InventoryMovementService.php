<?php

// FILE: app/Support/Inventory/InventoryMovementService.php | V2

namespace App\Support\Inventory;

use App\Models\Document;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Catalogs\TaskCatalog;
use App\Support\System\OwnerAlertTaskService;
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
            $movement = InventoryMovement::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'order_id' => $order?->id,
                'document_id' => $document?->id,
                'kind' => $kind,
                'quantity' => $normalizedQuantity,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $this->notifyOwnerIfStockTurnsNegative(
                product: $product,
                movement: $movement,
                order: $order,
                actorUserId: $createdBy,
                movementQuantity: $normalizedQuantity,
            );

            return $movement;
        });
    }

    protected function notifyOwnerIfStockTurnsNegative(
        Product $product,
        InventoryMovement $movement,
        ?Order $order = null,
        int|string|null $actorUserId = null,
        float $movementQuantity = 0.0,
    ): void {
        if (! in_array($movement->kind, [self::KIND_CONSUMIR, self::KIND_ENTREGAR], true)) {
            return;
        }

        $stockAfter = app(ProductStockCalculator::class)->forProduct($product);

        if ($stockAfter >= 0) {
            return;
        }

        $actorUser = $this->resolveActorUser($actorUserId);

        $summary = sprintf(
            'El producto %s quedó con stock negativo luego de un movimiento de tipo %s por %s. Stock resultante: %s.',
            $product->name ?: 'Producto #'.$product->id,
            $movement->kind,
            $this->formatQuantity($movementQuantity, $product->unit_label),
            $this->formatQuantity($stockAfter, $product->unit_label)
        );

        $descriptionLines = [
            'Se registró un movimiento que dejó stock negativo y requiere revisión.',
            '',
            'Resumen:',
            $summary,
            '',
            'Detalle del evento:',
            '- Producto: '.($product->name ?: 'Producto #'.$product->id),
            '- SKU: '.($product->sku ?: '—'),
            '- Tipo de movimiento: '.$movement->kind,
            '- Cantidad del movimiento: '.$this->formatQuantity($movementQuantity, $product->unit_label),
            '- Stock resultante: '.$this->formatQuantity($stockAfter, $product->unit_label),
            '- Fecha y hora del evento: '.($movement->created_at?->format('d/m/Y H:i:s') ?: '—'),
            '- Usuario interviniente: '.($actorUser?->name ?: 'Sistema'),
        ];

        if ($order) {
            $descriptionLines[] = '- Orden relacionada: '.($order->number ?: 'Orden #'.$order->id);
        }

        if ($movement->notes) {
            $descriptionLines[] = '- Notas del movimiento: '.$movement->notes;
        }

        app(OwnerAlertTaskService::class)->createOnceForTenant(
            tenant: app('tenant'),
            type: 'inventory_negative_stock',
            title: 'Revisar desvío de stock: '.($product->name ?: 'Producto #'.$product->id),
            description: implode("\n", $descriptionLines),
            dedupeKey: 'inventory_negative_stock:product:'.$product->id,
            metadata: [
                'source' => 'inventory',
                'summary' => $summary,
                'occurred_at' => $movement->created_at?->toDateTimeString(),
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'order_id' => $order?->id,
                'order_number' => $order?->number,
                'inventory_movement_id' => $movement->id,
                'movement_kind' => $movement->kind,
                'movement_quantity' => $movementQuantity,
                'stock_after' => $stockAfter,
                'actor_user_id' => $actorUser?->id,
                'actor_user_name' => $actorUser?->name,
            ],
            priority: TaskCatalog::PRIORITY_HIGH,
            dueDate: now()->toDateString(),
        );
    }

    protected function resolveActorUser(int|string|null $actorUserId = null): ?User
    {
        if ($actorUserId === null || $actorUserId === '') {
            return null;
        }

        return User::query()->find($actorUserId);
    }

    protected function formatQuantity(float $quantity, ?string $unitLabel = null): string
    {
        $formatted = number_format($quantity, 2, ',', '.');

        return $unitLabel
            ? $formatted.' '.$unitLabel
            : $formatted;
    }
}
