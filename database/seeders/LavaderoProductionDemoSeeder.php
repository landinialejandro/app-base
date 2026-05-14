<?php

// FILE: database/seeders/LavaderoProductionDemoSeeder.php | V1

namespace Database\Seeders;

use App\Events\OperationalRecordCreated;
use App\Events\OperationalRecordUpdated;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SelfServiceShop;
use App\Models\SelfServiceShopItem;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\OrderInventoryOperationService;
use App\Support\Inventory\OrderItemStatusService;
use App\Support\SelfServiceSales\SelfServiceShopPublisher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LavaderoProductionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()
            ->where('slug', 'lavadero-sa')
            ->firstOrFail();

        $owner = User::query()
            ->where('email', 'santiago.mendez@lavaderosa.local')
            ->firstOrFail();

        $hadTenantBinding = app()->bound('tenant');
        $previousTenant = $hadTenantBinding ? app('tenant') : null;

        app()->instance('tenant', $tenant);

        try {
            $ficha = $this->updateFichaProduct($tenant->id, $owner->id);
            $this->updateTiempoProduct($tenant->id, $owner->id);
            $this->ensureActiveShopCatalog($tenant, $ficha);

            $this->ensureProductionSequence($tenant->id);

            $order = $this->createOrUpdateProductionOrder(
                tenantId: $tenant->id,
                ownerUserId: $owner->id,
            );

            $item = $this->createOrUpdateProductionItem(
                tenantId: $tenant->id,
                orderId: $order->id,
                product: $ficha,
            );

            $this->executeProductionIfPending(
                order: $order->fresh(['items.product']),
                item: $item->fresh(['product']),
                ownerUserId: $owner->id,
            );
        } finally {
            if ($hadTenantBinding) {
                app()->instance('tenant', $previousTenant);
            } else {
                app()->forgetInstance('tenant');
            }
        }
    }

    private function updateFichaProduct(string $tenantId, int $ownerUserId): Product
    {
        $product = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('sku', 'LAV-FICHA')
            ->firstOrFail();

        $beforeAttributes = $product->getAttributes();

        $product->update([
            'name' => 'Ficha lavado 5 minutos',
            'kind' => ProductCatalog::KIND_PRODUCT,
            'unit_label' => 'ficha',
            'price' => 1500,
            'description' => 'Unidad stockeable de derecho de uso para activar un ciclo de lavado autoservicio de 5 minutos.',
            'is_active' => true,
        ]);

        if ($product->wasChanged()) {
            event(new OperationalRecordUpdated(
                record: $product,
                beforeAttributes: $beforeAttributes,
                actorUserId: $ownerUserId,
            ));
        }

        return $product->fresh();
    }

    private function updateTiempoProduct(string $tenantId, int $ownerUserId): Product
    {
        $product = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('sku', 'LAV-TIEMPO-SEG')
            ->firstOrFail();

        $beforeAttributes = $product->getAttributes();

        $product->update([
            'name' => 'Tiempo en segundos',
            'kind' => ProductCatalog::KIND_SERVICE,
            'unit_label' => 'segundo',
            'price' => 5,
            'description' => 'Unidad técnica para parametrizar la duración y valor de los ciclos de lavado.',
            'is_active' => true,
        ]);

        if ($product->wasChanged()) {
            event(new OperationalRecordUpdated(
                record: $product,
                beforeAttributes: $beforeAttributes,
                actorUserId: $ownerUserId,
            ));
        }

        return $product->fresh();
    }

    private function ensureActiveShopCatalog(Tenant $tenant, Product $ficha): void
    {
        $shop = SelfServiceShop::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Autoservicio '.$tenant->name,
            ],
            [
                'description' => 'Catálogo publicado inicial para clientes habilitados.',
                'status' => SelfServiceShop::STATUS_DRAFT,
                'meta' => [
                    'source' => 'LavaderoProductionDemoSeeder',
                ],
            ]
        );

        app(SelfServiceShopPublisher::class)->activate($shop);

        SelfServiceShopItem::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'self_service_shop_id' => $shop->id,
                'product_id' => $ficha->id,
            ],
            [
                'is_visible' => true,
                'use_product_price' => true,
                'price' => null,
                'display_name' => null,
                'display_description' => null,
                'sort_order' => 10,
                'status' => SelfServiceShopItem::STATUS_PUBLISHED,
                'meta' => [
                    'source' => 'LavaderoProductionDemoSeeder',
                ],
            ]
        );
    }

    private function ensureProductionSequence(string $tenantId): void
    {
        DB::table('document_sequences')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'doc_type' => 'order.production',
                'point_of_sale' => '0001',
            ],
            [
                'branch_id' => null,
                'prefix' => 'OPR',
                'padding' => 8,
                'next_number' => 2,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function createOrUpdateProductionOrder(string $tenantId, int $ownerUserId): Order
    {
        $order = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('number', 'LAV-OPR-0001')
            ->first();

        if ($order) {
            $beforeAttributes = $order->getAttributes();

            $order->update([
                'party_id' => null,
                'counterparty_reference' => 'Producción interna Lavadero SA',
                'group' => OrderCatalog::GROUP_PRODUCTION,
                'kind' => OrderCatalog::KIND_STANDARD,
                'status' => OrderCatalog::STATUS_APPROVED,
                'ordered_at' => now()->toDateString(),
                'notes' => 'Producción diaria de fichas de lavado. Capacidad calculada sobre 24 horas de operación y ciclos de 5 minutos.',
                'updated_by' => $ownerUserId,
            ]);

            if ($order->wasChanged()) {
                event(new OperationalRecordUpdated(
                    record: $order,
                    beforeAttributes: $beforeAttributes,
                    actorUserId: $ownerUserId,
                ));
            }

            return $order->fresh();
        }

        $order = Order::create([
            'tenant_id' => $tenantId,
            'party_id' => null,
            'counterparty_reference' => 'Producción interna Lavadero SA',
            'asset_id' => null,
            'asset_reference' => null,
            'group' => OrderCatalog::GROUP_PRODUCTION,
            'kind' => OrderCatalog::KIND_STANDARD,
            'number' => 'LAV-OPR-0001',
            'sequence_prefix' => 'OPR',
            'point_of_sale' => '0001',
            'sequence_number' => 1,
            'status' => OrderCatalog::STATUS_APPROVED,
            'ordered_at' => now()->toDateString(),
            'notes' => 'Producción diaria de fichas de lavado. Capacidad calculada sobre 24 horas de operación y ciclos de 5 minutos.',
            'created_by' => $ownerUserId,
            'updated_by' => $ownerUserId,
        ]);

        event(new OperationalRecordCreated(
            record: $order,
            actorUserId: $ownerUserId,
        ));

        return $order->fresh();
    }

    private function createOrUpdateProductionItem(string $tenantId, int $orderId, Product $product): OrderItem
    {
        $quantity = 288.0;
        $unitPrice = 1500.0;

        $payload = [
            'tenant_id' => $tenantId,
            'order_id' => $orderId,
            'product_id' => $product->id,
            'description' => 'Ficha lavado 5 minutos',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('order_items', 'kind')) {
            $payload['kind'] = ProductCatalog::KIND_PRODUCT;
        }

        if (Schema::hasColumn('order_items', 'total')) {
            $payload['total'] = round($quantity * $unitPrice, 2);
        }

        if (Schema::hasColumn('order_items', 'status')) {
            $payload['status'] = 'pending';
        }

        $existing = OrderItem::query()
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->update($payload);

            return $existing->fresh();
        }

        $payload['created_at'] = now();

        DB::table('order_items')->insert($payload);

        return OrderItem::query()
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->where('product_id', $product->id)
            ->latest('id')
            ->firstOrFail();
    }

private function executeProductionIfPending(Order $order, OrderItem $item, int $ownerUserId): void
    {
        $item->loadMissing('product');

        $pendingQuantity = app(OrderItemStatusService::class)->pendingQuantity($item);

        if ($pendingQuantity <= 0) {
            app(OrderItemStatusService::class)->recalculate($item);

            return;
        }

        app(OrderInventoryOperationService::class)->executeLine(
            order: $order,
            item: $item,
            quantity: $pendingQuantity,
            notes: 'Ingreso de fichas producidas por orden diaria.',
            createdBy: $ownerUserId,
        );
    }
}
