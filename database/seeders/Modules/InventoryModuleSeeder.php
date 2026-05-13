<?php

// FILE: database/seeders/Modules/InventoryModuleSeeder.php | V4

namespace Database\Seeders\Modules;

use App\Models\Document;
use App\Models\InventoryMovement;
use App\Models\InventoryOperation;
use App\Models\Order;
use App\Models\Product;
use App\Support\Catalogs\DocumentCatalog;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\InventoryMovementService;
use App\Support\Inventory\InventoryOperationCatalog;
use App\Support\Inventory\InventoryOperationProfileResolver;
use App\Support\Inventory\InventoryOperationService;
use App\Support\Inventory\InventoryOriginCatalog;
use Illuminate\Support\Collection;

class InventoryModuleSeeder extends BaseModuleSeeder
{
    private const SEED_NOTE_PREFIX = '[seed][inventory-v3]';

    public function run(): void
    {
        if (
            ! $this->hasDependency('tenants')
            || ! $this->hasDependency('users')
            || ! $this->hasDependency('products')
            || ! $this->hasDependency('orders')
        ) {
            throw new \RuntimeException('InventoryModuleSeeder requires tenants, users, products, and orders');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $products = $this->getDependency('products');
        $orders = $this->getDependency('orders');
        $documents = $this->getDependency('documents') ?? [];

        $movements = [];

        $movements['tech'] = $this->seedTenantInventory(
            tenantId: $tenants['tech']->id,
            actorUserId: $users['ownerTech']->id,
            products: $products['tech'] ?? collect(),
            orders: $orders['tech'] ?? collect(),
            documents: $documents['tech'] ?? collect(),
        );

        $movements['andina'] = $this->seedTenantInventory(
            tenantId: $tenants['andina']->id,
            actorUserId: $users['ownerAndina']->id,
            products: $products['andina'] ?? collect(),
            orders: $orders['andina'] ?? collect(),
            documents: $documents['andina'] ?? collect(),
        );

        $movements['lavadero'] = $this->seedTenantInventory(
            tenantId: $tenants['lavadero']->id,
            actorUserId: $users['lavaderoOwner']->id,
            products: $products['lavadero'] ?? collect(),
            orders: $orders['lavadero'] ?? collect(),
            documents: $documents['lavadero'] ?? collect(),
        );

        $this->context['inventory_movements'] = $movements;
    }

private function seedTenantInventory(
        string $tenantId,
        int $actorUserId,
        Collection $products,
        Collection $orders,
        Collection $documents
    ): Collection {
        $physicalProducts = $products
            ->filter(fn ($product) => $product instanceof Product)
            ->filter(fn (Product $product) => $product->kind === ProductCatalog::KIND_PRODUCT)
            ->values();

        if ($physicalProducts->isEmpty()) {
            return collect();
        }

        $this->cleanupSeededInventory($tenantId, $physicalProducts);

        $created = collect();

        $initialStockProducts = $physicalProducts
            ->filter(fn (Product $product) => $this->shouldSeedInitialStockForProduct($product))
            ->values();

        if ($initialStockProducts->isNotEmpty()) {
            $initialOperation = $this->createInventoryOperation(
                tenantId: $tenantId,
                operationType: InventoryOperationCatalog::TYPE_MANUAL_ADJUSTMENT,
                notes: $this->seedNote('Ingreso inicial de stock demo'),
                createdBy: $actorUserId,
            );

            foreach ($initialStockProducts as $index => $product) {
                $created->push(
                    InventoryMovement::create([
                        'tenant_id' => $tenantId,
                        'product_id' => $product->id,
                        'inventory_operation_id' => $initialOperation->id,
                        'origin_type' => InventoryOriginCatalog::TYPE_MANUAL,
                        'origin_id' => null,
                        'origin_line_type' => null,
                        'origin_line_id' => null,
                        'kind' => InventoryMovementService::KIND_INGRESAR,
                        'quantity' => $this->initialQuantityForProduct($product, $index),
                        'notes' => $this->seedNote("Ingreso inicial de stock para {$product->sku}"),
                        'created_by' => $actorUserId,
                        'created_at' => now()->subDays(10 - min($index, 9)),
                        'updated_at' => now()->subDays(10 - min($index, 9)),
                    ])
                );
            }
        }

        foreach ($orders as $order) {
            if (! $order instanceof Order || $order->status !== 'approved') {
                continue;
            }

            if ($order->group === \App\Support\Catalogs\OrderCatalog::GROUP_PRODUCTION) {
                continue;
            }

            $order->loadMissing('items.product');

            $physicalItems = $order->items
                ->filter(fn ($item) => $item->product instanceof Product)
                ->filter(fn ($item) => $item->product->kind === ProductCatalog::KIND_PRODUCT)
                ->values();

            if ($physicalItems->isEmpty()) {
                continue;
            }

            $profile = app(InventoryOperationProfileResolver::class)->forOrder($order);

            $orderOperation = $this->createInventoryOperation(
                tenantId: $tenantId,
                operationType: InventoryOperationCatalog::TYPE_ORDER_LINE_EXECUTE,
                originType: InventoryOriginCatalog::TYPE_ORDER,
                originId: $order->id,
                notes: $this->seedNote("Operación por orden {$order->number}"),
                createdBy: $actorUserId,
            );

            foreach ($physicalItems as $position => $item) {
                $quantity = (float) $item->quantity;

                if ($quantity <= 0) {
                    continue;
                }

                $created->push(
                    InventoryMovement::create([
                        'tenant_id' => $tenantId,
                        'product_id' => $item->product->id,
                        'inventory_operation_id' => $orderOperation->id,
                        'origin_type' => InventoryOriginCatalog::TYPE_ORDER,
                        'origin_id' => $order->id,
                        'origin_line_type' => InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM,
                        'origin_line_id' => $item->id,
                        'kind' => $profile['execute_kind'],
                        'quantity' => $quantity,
                        'notes' => $this->seedNote("Movimiento por orden {$order->number}"),
                        'created_by' => $actorUserId,
                        'created_at' => now()->subDays(4)->addMinutes($position),
                        'updated_at' => now()->subDays(4)->addMinutes($position),
                    ])
                );
            }
        }

        foreach ($documents as $document) {
            if (! is_object($document) || ! isset($document->id)) {
                continue;
            }

            $documentModel = Document::query()
                ->with('items.product')
                ->find($document->id);

            if (! $documentModel) {
                continue;
            }

            $direction = DocumentCatalog::stockDirection($documentModel->group, $documentModel->kind);

            $movementKind = match ($direction) {
                'in' => InventoryMovementService::KIND_INGRESAR,
                'out' => InventoryMovementService::KIND_ENTREGAR,
                default => null,
            };

            if ($movementKind === null) {
                continue;
            }

            $physicalItems = $documentModel->items
                ->filter(fn ($item) => $item->product instanceof Product)
                ->filter(fn ($item) => $item->product->kind === ProductCatalog::KIND_PRODUCT)
                ->values();

            if ($physicalItems->isEmpty()) {
                continue;
            }

            $documentOperation = $this->createInventoryOperation(
                tenantId: $tenantId,
                operationType: InventoryOperationCatalog::TYPE_DOCUMENT_MOVEMENT,
                originType: InventoryOriginCatalog::TYPE_DOCUMENT,
                originId: $documentModel->id,
                notes: $this->seedNote("Operación por documento {$documentModel->number}"),
                createdBy: $actorUserId,
            );

            foreach ($physicalItems as $position => $item) {
                $quantity = (float) $item->quantity;

                if ($quantity <= 0) {
                    continue;
                }

                $created->push(
                    InventoryMovement::create([
                        'tenant_id' => $tenantId,
                        'product_id' => $item->product->id,
                        'inventory_operation_id' => $documentOperation->id,
                        'origin_type' => InventoryOriginCatalog::TYPE_DOCUMENT,
                        'origin_id' => $documentModel->id,
                        'origin_line_type' => InventoryOriginCatalog::LINE_TYPE_DOCUMENT_ITEM,
                        'origin_line_id' => $item->id,
                        'kind' => $movementKind,
                        'quantity' => $quantity,
                        'notes' => $this->seedNote("Movimiento por documento {$documentModel->number}"),
                        'created_by' => $actorUserId,
                        'created_at' => now()->subDays(3)->addMinutes($position),
                        'updated_at' => now()->subDays(3)->addMinutes($position),
                    ])
                );
            }
        }

        return $created->sortBy('id')->values();
    }

    private function createInventoryOperation(
        string $tenantId,
        string $operationType,
        ?string $originType = null,
        int|string|null $originId = null,
        ?string $originLineType = null,
        int|string|null $originLineId = null,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): InventoryOperation {
        return app(InventoryOperationService::class)->create(
            tenantId: $tenantId,
            operationType: $operationType,
            originType: $originType,
            originId: $originId,
            originLineType: $originLineType,
            originLineId: $originLineId,
            notes: $notes,
            createdBy: $createdBy,
        );
    }

    private function cleanupSeededInventory(string $tenantId, Collection $physicalProducts): void
    {
        $productIds = $physicalProducts->pluck('id')->all();

        if (! empty($productIds)) {
            InventoryMovement::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('product_id', $productIds)
                ->where('notes', 'like', '[seed][inventory-%')
                ->forceDelete();
        }

        InventoryOperation::query()
            ->where('tenant_id', $tenantId)
            ->where('notes', 'like', '[seed][inventory-%')
            ->forceDelete();
    }

    private function initialQuantityForProduct(Product $product, int $index): float
    {
        return match ($product->sku) {
            'LAV-INS-SHAMPOO' => 80.0,
            'LAV-INS-CERA' => 45.0,
            'LAV-PROD-SILICONA' => 30.0,
            'LAV-PROD-FRANELA' => 60.0,
            'LAV-PROD-AROMA' => 75.0,
            default => $this->initialQuantityForIndex($index),
        };
    }

    private function initialQuantityForIndex(int $index): float
    {
        return match ($index) {
            0 => 20.0,
            1 => 12.0,
            2 => 6.0,
            default => 10.0,
        };
    }

    private function seedNote(string $text): string
    {
        return self::SEED_NOTE_PREFIX.' '.$text;
    }


    private function shouldSeedInitialStockForProduct(Product $product): bool
        {
            return $product->sku !== 'LAV-FICHA';
        }
}
