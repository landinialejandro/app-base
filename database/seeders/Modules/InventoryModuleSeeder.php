<?php

// FILE: database/seeders/Modules/InventoryModuleSeeder.php | V1

namespace Database\Seeders\Modules;

use App\Models\Document;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\InventoryMovementService;
use Illuminate\Support\Collection;

class InventoryModuleSeeder extends BaseModuleSeeder
{
    private const SEED_NOTE_PREFIX = '[seed][inventory-v1]';

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

        $this->cleanupSeededMovements($tenantId, $physicalProducts);

        $created = collect();

        // 1) Ingreso inicial por producto físico
        foreach ($physicalProducts as $index => $product) {
            $created->push(
                InventoryMovement::create([
                    'tenant_id' => $tenantId,
                    'product_id' => $product->id,
                    'order_id' => null,
                    'document_id' => null,
                    'kind' => InventoryMovementService::KIND_INGRESAR,
                    'quantity' => $this->initialQuantityForIndex($index),
                    'notes' => $this->seedNote("Ingreso inicial de stock para {$product->sku}"),
                    'created_by' => $actorUserId,
                    'created_at' => now()->subDays(10 - $index),
                    'updated_at' => now()->subDays(10 - $index),
                ])
            );
        }

        // 2) Movimientos operativos derivados de órdenes reales
        foreach ($orders as $order) {
            if (! $order instanceof Order) {
                continue;
            }

            if ($order->status !== 'approved') {
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

            foreach ($physicalItems as $position => $item) {
                $product = $item->product;
                $quantity = (float) $item->quantity;

                if ($quantity <= 0) {
                    continue;
                }

                if ($order->kind === 'purchase') {
                    $created->push(
                        InventoryMovement::create([
                            'tenant_id' => $tenantId,
                            'product_id' => $product->id,
                            'order_id' => $order->id,
                            'document_id' => null,
                            'kind' => InventoryMovementService::KIND_INGRESAR,
                            'quantity' => $quantity,
                            'notes' => $this->seedNote("Ingreso por orden {$order->number}"),
                            'created_by' => $actorUserId,
                            'created_at' => now()->subDays(4)->addMinutes($position),
                            'updated_at' => now()->subDays(4)->addMinutes($position),
                        ])
                    );

                    continue;
                }

                if ($order->kind === 'sale') {
                    $created->push(
                        InventoryMovement::create([
                            'tenant_id' => $tenantId,
                            'product_id' => $product->id,
                            'order_id' => $order->id,
                            'document_id' => null,
                            'kind' => InventoryMovementService::KIND_ENTREGAR,
                            'quantity' => $quantity,
                            'notes' => $this->seedNote("Entrega por orden {$order->number}"),
                            'created_by' => $actorUserId,
                            'created_at' => now()->subDays(2)->addMinutes($position),
                            'updated_at' => now()->subDays(2)->addMinutes($position),
                        ])
                    );

                    continue;
                }

                if ($order->kind === 'service') {
                    $created->push(
                        InventoryMovement::create([
                            'tenant_id' => $tenantId,
                            'product_id' => $product->id,
                            'order_id' => $order->id,
                            'document_id' => null,
                            'kind' => InventoryMovementService::KIND_CONSUMIR,
                            'quantity' => $quantity,
                            'notes' => $this->seedNote("Consumo por orden {$order->number}"),
                            'created_by' => $actorUserId,
                            'created_at' => now()->subDay()->addMinutes($position),
                            'updated_at' => now()->subDay()->addMinutes($position),
                        ])
                    );
                }
            }
        }

        // 3) Ingreso documental mínimo cuando exista documento de compra/soporte físico sin duplicar órdenes
        // Solo toma documentos con ítems físicos y sin order_id para no duplicar movimientos ya representados por órdenes.
        foreach ($documents as $document) {
            if (! is_object($document) || ! isset($document->id)) {
                continue;
            }

            if (! empty($document->order_id)) {
                continue;
            }

            $documentModel = Document::query()
                ->with('items.product')
                ->find($document->id);

            if (! $documentModel) {
                continue;
            }

            $physicalItems = $documentModel->items
                ->filter(fn ($item) => $item->product instanceof Product)
                ->filter(fn ($item) => $item->product->kind === ProductCatalog::KIND_PRODUCT)
                ->values();

            foreach ($physicalItems as $position => $item) {
                $created->push(
                    InventoryMovement::create([
                        'tenant_id' => $tenantId,
                        'product_id' => $item->product->id,
                        'order_id' => null,
                        'document_id' => $documentModel->id,
                        'kind' => InventoryMovementService::KIND_INGRESAR,
                        'quantity' => (float) $item->quantity,
                        'notes' => $this->seedNote("Ingreso por documento {$documentModel->number}"),
                        'created_by' => $actorUserId,
                        'created_at' => now()->subDays(3)->addMinutes($position),
                        'updated_at' => now()->subDays(3)->addMinutes($position),
                    ])
                );
            }
        }

        return $created->sortBy('id')->values();
    }

    private function cleanupSeededMovements(string $tenantId, Collection $physicalProducts): void
    {
        $productIds = $physicalProducts->pluck('id')->all();

        if (empty($productIds)) {
            return;
        }

        InventoryMovement::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('product_id', $productIds)
            ->where('notes', 'like', self::SEED_NOTE_PREFIX.'%')
            ->forceDelete();
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
}
