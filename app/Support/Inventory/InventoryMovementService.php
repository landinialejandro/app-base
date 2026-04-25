<?php

// FILE: app/Support/Inventory/InventoryMovementService.php | V8

namespace App\Support\Inventory;

use App\Models\Document;
use App\Models\InventoryMovement;
use App\Models\InventoryOperation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Task;
use App\Models\User;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
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
        ?InventoryOperation $operation = null,
    ): array {
        return $this->createMovement(
            product: $product,
            kind: self::KIND_INGRESAR,
            quantity: $quantity,
            notes: $notes,
            order: $order,
            orderItem: null,
            document: $document,
            createdBy: $createdBy,
            operation: $operation,
        );
    }

    public function consumir(
        Product $product,
        float|int|string $quantity,
        ?string $notes = null,
        ?Order $order = null,
        ?Document $document = null,
        int|string|null $createdBy = null,
        ?InventoryOperation $operation = null,
    ): array {
        return $this->createMovement(
            product: $product,
            kind: self::KIND_CONSUMIR,
            quantity: $quantity,
            notes: $notes,
            order: $order,
            orderItem: null,
            document: $document,
            createdBy: $createdBy,
            operation: $operation,
        );
    }

    public function entregar(
        Product $product,
        float|int|string $quantity,
        ?string $notes = null,
        ?Order $order = null,
        ?Document $document = null,
        int|string|null $createdBy = null,
        ?InventoryOperation $operation = null,
    ): array {
        return $this->createMovement(
            product: $product,
            kind: self::KIND_ENTREGAR,
            quantity: $quantity,
            notes: $notes,
            order: $order,
            orderItem: null,
            document: $document,
            createdBy: $createdBy,
            operation: $operation,
        );
    }

    public function createForOrderItem(
        Order $order,
        OrderItem $item,
        Product $product,
        string $kind,
        float|int|string $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
        ?InventoryOperation $operation = null,
    ): array {
        return $this->createMovement(
            product: $product,
            kind: $kind,
            quantity: $quantity,
            notes: $notes,
            order: $order,
            orderItem: $item,
            document: null,
            createdBy: $createdBy,
            operation: $operation,
        );
    }

    protected function createMovement(
        Product $product,
        string $kind,
        float|int|string $quantity,
        ?string $notes = null,
        ?Order $order = null,
        ?OrderItem $orderItem = null,
        ?Document $document = null,
        int|string|null $createdBy = null,
        ?InventoryOperation $operation = null,
    ): array {
        $normalizedQuantity = (float) $quantity;

        $this->validateKind($kind);
        $this->validateQuantity($normalizedQuantity);
        $this->validateTenantConsistency($product, $order, $orderItem, $document, $operation);
        $this->validateOrderContext($product, $order, $orderItem);
        $this->validateDocumentContext($product, $document);

        $origin = $this->resolveOrigin($order, $document);
        $originLine = $this->resolveOriginLine($orderItem);

        return DB::transaction(function () use (
            $product,
            $kind,
            $normalizedQuantity,
            $notes,
            $order,
            $orderItem,
            $document,
            $createdBy,
            $operation,
            $origin,
            $originLine,
        ) {
            $movement = InventoryMovement::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'inventory_operation_id' => $operation?->id,
                'origin_type' => $origin['type'],
                'origin_id' => $origin['id'],
                'origin_line_type' => $originLine['type'],
                'origin_line_id' => $originLine['id'],
                'kind' => $kind,
                'quantity' => $normalizedQuantity,
                'notes' => $this->buildTraceableNotes(
                    kind: $kind,
                    quantity: $normalizedQuantity,
                    userNotes: $notes,
                    product: $product,
                    order: $order,
                    orderItem: $orderItem,
                    document: $document,
                    createdBy: $createdBy,
                    operation: $operation,
                ),
                'created_by' => $createdBy,
            ]);

            $stockAfter = app(ProductStockCalculator::class)->forProduct($product);

            if ($orderItem) {
                app(OrderItemStatusService::class)->recalculate($orderItem);
            }

            $ownerAlertTask = $this->notifyOwnerIfStockTurnsNegative(
                product: $product,
                movement: $movement,
                stockAfter: $stockAfter,
                order: $order,
                orderItem: $orderItem,
                actorUserId: $createdBy,
                movementQuantity: $normalizedQuantity,
            );

            return [
                'operation' => $operation,
                'movement' => $movement,
                'stock_after' => $stockAfter,
                'negative_stock' => $stockAfter < 0,
                'owner_alert_task' => $ownerAlertTask,
            ];
        });
    }

    protected function resolveOrigin(?Order $order = null, ?Document $document = null): array
    {
        if ($order) {
            return [
                'type' => InventoryOriginCatalog::TYPE_ORDER,
                'id' => $order->id,
            ];
        }

        if ($document) {
            return [
                'type' => InventoryOriginCatalog::TYPE_DOCUMENT,
                'id' => $document->id,
            ];
        }

        return [
            'type' => InventoryOriginCatalog::TYPE_MANUAL,
            'id' => null,
        ];
    }

    protected function resolveOriginLine(?OrderItem $orderItem = null): array
    {
        if ($orderItem) {
            return [
                'type' => InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM,
                'id' => $orderItem->id,
            ];
        }

        return [
            'type' => null,
            'id' => null,
        ];
    }

    protected function validateKind(string $kind): void
    {
        if (! in_array($kind, self::kinds(), true)) {
            throw new InvalidArgumentException('Tipo de movimiento inválido.');
        }
    }

    protected function validateQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }
    }

    protected function validateTenantConsistency(
        Product $product,
        ?Order $order = null,
        ?OrderItem $orderItem = null,
        ?Document $document = null,
        ?InventoryOperation $operation = null,
    ): void {
        if ($order && $order->tenant_id !== $product->tenant_id) {
            throw new InvalidArgumentException('La orden pertenece a otro tenant.');
        }

        if ($orderItem && $orderItem->tenant_id !== $product->tenant_id) {
            throw new InvalidArgumentException('La línea de la orden pertenece a otro tenant.');
        }

        if ($document && $document->tenant_id !== $product->tenant_id) {
            throw new InvalidArgumentException('El documento pertenece a otro tenant.');
        }

        if ($operation && $operation->tenant_id !== $product->tenant_id) {
            throw new InvalidArgumentException('La operación de inventario pertenece a otro tenant.');
        }
    }

    protected function validateOrderContext(
        Product $product,
        ?Order $order = null,
        ?OrderItem $orderItem = null,
    ): void {
        if (! $order && ! $orderItem) {
            return;
        }

        if (! $order && $orderItem) {
            throw new InvalidArgumentException('La línea requiere una orden asociada.');
        }

        if ($order && ! $orderItem) {
            throw new InvalidArgumentException('No se admiten movimientos de orden sin línea asociada.');
        }

        if ((int) $orderItem->order_id !== (int) $order->id) {
            throw new InvalidArgumentException('La línea no pertenece a la orden indicada.');
        }

        if ((int) $orderItem->product_id !== (int) $product->id) {
            throw new InvalidArgumentException('La línea no corresponde al producto indicado.');
        }

        if ($order->status !== OrderCatalog::STATUS_APPROVED) {
            throw new InvalidArgumentException('La orden no está en estado operable para inventory.');
        }

        $this->validatePhysicalProduct($product);
    }

    protected function validateDocumentContext(Product $product, ?Document $document = null): void
    {
        if (! $document) {
            $this->validatePhysicalProduct($product);

            return;
        }

        $this->validatePhysicalProduct($product);
    }

    protected function validatePhysicalProduct(Product $product): void
    {
        if ($product->kind !== ProductCatalog::KIND_PRODUCT) {
            throw new InvalidArgumentException('El movimiento solo puede registrarse sobre productos físicos stockeables.');
        }
    }

    protected function buildTraceableNotes(
        string $kind,
        float $quantity,
        ?string $userNotes = null,
        ?Product $product = null,
        ?Order $order = null,
        ?OrderItem $orderItem = null,
        ?Document $document = null,
        int|string|null $createdBy = null,
        ?InventoryOperation $operation = null,
    ): string {
        $trace = [];

        $trace[] = '[inventory] Movimiento registrado por sistema';
        $trace[] = 'Tipo: '.$kind;
        $trace[] = 'Cantidad: '.$this->formatQuantity($quantity, $product?->unit_label);

        if ($operation) {
            $trace[] = 'Operación inventory #'.$operation->id;
            $trace[] = 'Tipo operación: '.$operation->operation_type;
        }

        if ($product) {
            $trace[] = 'Producto: '.($product->name ?: 'Producto #'.$product->id);
        }

        if ($order) {
            $trace[] = 'Origen: order #'.$order->id;
            $trace[] = 'Orden: '.($order->number ?: 'Orden #'.$order->id);
        } elseif ($document) {
            $trace[] = 'Origen: document #'.$document->id;
            $trace[] = 'Documento: '.($document->number ?: 'Documento #'.$document->id);
        } else {
            $trace[] = 'Origen: manual';
        }

        if ($orderItem) {
            $trace[] = 'Línea origen: order_item #'.$orderItem->id;
            $trace[] = 'Posición de línea: '.($orderItem->position ?? '—');
        }

        $actor = $this->resolveActorUser($createdBy);

        if ($actor) {
            $trace[] = 'Usuario: '.($actor->name ?: 'Usuario #'.$actor->id);
        }

        if ($userNotes && trim($userNotes) !== '') {
            $trace[] = 'Nota usuario: '.trim($userNotes);
        }

        return implode(' | ', $trace);
    }

    protected function notifyOwnerIfStockTurnsNegative(
        Product $product,
        InventoryMovement $movement,
        float $stockAfter,
        ?Order $order = null,
        ?OrderItem $orderItem = null,
        int|string|null $actorUserId = null,
        float $movementQuantity = 0.0,
    ): ?Task {
        if (! in_array($movement->kind, [self::KIND_CONSUMIR, self::KIND_ENTREGAR], true)) {
            return null;
        }

        if ($stockAfter >= 0) {
            return null;
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
            '- Origen: '.($movement->origin_type ?: '—').' '.($movement->origin_id ? '#'.$movement->origin_id : ''),
        ];

        if ($movement->inventory_operation_id) {
            $descriptionLines[] = '- Operación inventory: #'.$movement->inventory_operation_id;
        }

        if ($order) {
            $descriptionLines[] = '- Orden relacionada: '.($order->number ?: 'Orden #'.$order->id);
        }

        if ($orderItem) {
            $descriptionLines[] = '- Línea relacionada: #'.$orderItem->id.' - '.($orderItem->description ?: 'Sin descripción');
        }

        if ($movement->notes) {
            $descriptionLines[] = '- Notas del movimiento: '.$movement->notes;
        }

        return app(OwnerAlertTaskService::class)->createOnceForTenant(
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
                'inventory_operation_id' => $movement->inventory_operation_id,
                'origin_type' => $movement->origin_type,
                'origin_id' => $movement->origin_id,
                'origin_line_type' => $movement->origin_line_type,
                'origin_line_id' => $movement->origin_line_id,
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