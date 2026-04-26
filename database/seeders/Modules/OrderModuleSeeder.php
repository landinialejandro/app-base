<?php

// FILE: database/seeders/Modules/OrderModuleSeeder.php | V3

namespace Database\Seeders\Modules;

use App\Models\Order;
use App\Support\Catalogs\OrderCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (
            ! $this->hasDependency('tenants')
            || ! $this->hasDependency('users')
            || ! $this->hasDependency('parties')
            || ! $this->hasDependency('products')
        ) {
            throw new \RuntimeException('OrderModuleSeeder requires tenants, users, parties, and products');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $parties = $this->getDependency('parties');
        $products = $this->getDependency('products');

        $orders = [];
        $orders['tech'] = $this->createTechOrders(
            $tenants['tech'],
            $users,
            $parties['techFixed'],
            $products['tech']
        );

        $orders['andina'] = $this->createAndinaOrders(
            $tenants['andina'],
            $users,
            $parties['andinaFixed'],
            $products['andina']
        );

        $this->context['orders'] = $orders;
    }

    private function createTechOrders($tenant, array $users, $parties, $products): Collection
    {
        $orders = collect();

        $acme = $parties[0] ?? null;
        $laura = $parties[1] ?? null;

        $order1 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $acme?->id,
            'created_by' => $users['ownerTech']->id,
            'updated_by' => $users['ownerTech']->id,
            'kind' => OrderCatalog::KIND_SALE,
            'number' => 'TECH-ORD-0001',
            'status' => OrderCatalog::STATUS_DRAFT,
            'ordered_at' => now()->subDays(3)->toDateString(),
            'notes' => 'Pedido inicial de cliente estratégico.',
        ]);

        $this->replaceOrderItems($tenant->id, $order1->id, [
            ['product' => $products[0] ?? null, 'description' => 'Aceite 10W40', 'quantity' => 2, 'unit_price' => 18500],
            ['product' => $products[1] ?? null, 'description' => 'Filtro de aceite', 'quantity' => 1, 'unit_price' => 8500],
            ['product' => $products[3] ?? null, 'kind' => 'service', 'description' => 'Service general', 'quantity' => 1, 'unit_price' => 48000],
        ]);
        $orders->push($order1);

        $order2 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $laura?->id,
            'created_by' => $users['techUser']->id,
            'updated_by' => $users['techUser']->id,
            'kind' => OrderCatalog::KIND_SERVICE,
            'number' => 'TECH-ORD-0002',
            'status' => OrderCatalog::STATUS_APPROVED,
            'ordered_at' => now()->subDay()->toDateString(),
            'notes' => 'Trabajo técnico aprobado.',
        ]);

        $this->replaceOrderItems($tenant->id, $order2->id, [
            ['product' => $products[2] ?? null, 'description' => 'Kit transmisión', 'quantity' => 1, 'unit_price' => 69000],
            ['product' => $products[4] ?? null, 'description' => 'Diagnóstico', 'quantity' => 1, 'unit_price' => 22000],
        ]);
        $orders->push($order2);

        $order3 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $acme?->id,
            'created_by' => $users['shared']->id,
            'updated_by' => $users['shared']->id,
            'kind' => OrderCatalog::KIND_PURCHASE,
            'number' => 'TECH-ORD-0003',
            'status' => OrderCatalog::STATUS_CANCELLED,
            'ordered_at' => now()->subDays(2)->toDateString(),
            'notes' => 'Compra demo cancelada para probar estados.',
        ]);

        $this->replaceOrderItems($tenant->id, $order3->id, [
            ['product' => $products[1] ?? null, 'description' => 'Filtro de aceite', 'quantity' => 4, 'unit_price' => 8500],
            ['product' => $products[2] ?? null, 'description' => 'Kit transmisión', 'quantity' => 1, 'unit_price' => 69000],
        ]);
        $orders->push($order3);

        return $orders;
    }

    private function createAndinaOrders($tenant, array $users, $parties, $products): Collection
    {
        $orders = collect();

        $obrasPatagonicas = $parties[0] ?? null;
        $marcos = $parties[1] ?? null;

        $order1 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $obrasPatagonicas?->id,
            'created_by' => $users['ownerAndina']->id,
            'updated_by' => $users['ownerAndina']->id,
            'kind' => OrderCatalog::KIND_SALE,
            'number' => 'AND-ORD-0001',
            'status' => OrderCatalog::STATUS_DRAFT,
            'ordered_at' => now()->subDays(4)->toDateString(),
            'notes' => 'Materiales para avance de obra.',
        ]);

        $this->replaceOrderItems($tenant->id, $order1->id, [
            ['product' => $products[0] ?? null, 'description' => 'Hormigón H21', 'quantity' => 8, 'unit_price' => 125000],
            ['product' => $products[1] ?? null, 'description' => 'Hierro 8mm', 'quantity' => 30, 'unit_price' => 18500],
        ]);
        $orders->push($order1);

        $order2 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $marcos?->id,
            'created_by' => $users['andinaUser']->id,
            'updated_by' => $users['andinaUser']->id,
            'kind' => OrderCatalog::KIND_SERVICE,
            'number' => 'AND-ORD-0002',
            'status' => OrderCatalog::STATUS_APPROVED,
            'ordered_at' => now()->toDateString(),
            'notes' => 'Servicios técnicos aprobados.',
        ]);

        $this->replaceOrderItems($tenant->id, $order2->id, [
            ['product' => $products[2] ?? null, 'description' => 'Servicio topográfico', 'quantity' => 1, 'unit_price' => 150000],
            ['product' => $products[3] ?? null, 'description' => 'Inspección técnica', 'quantity' => 1, 'unit_price' => 98000],
        ]);
        $orders->push($order2);

        $order3 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $obrasPatagonicas?->id,
            'created_by' => $users['shared']->id,
            'updated_by' => $users['shared']->id,
            'kind' => OrderCatalog::KIND_PURCHASE,
            'number' => 'AND-ORD-0003',
            'status' => OrderCatalog::STATUS_CANCELLED,
            'ordered_at' => now()->subDays(3)->toDateString(),
            'notes' => 'Compra demo cancelada.',
        ]);

        $this->replaceOrderItems($tenant->id, $order3->id, [
            ['product' => $products[1] ?? null, 'description' => 'Hierro 8mm', 'quantity' => 10, 'unit_price' => 18500],
        ]);
        $orders->push($order3);

        return $orders;
    }

private function createOrder(array $data): Order
{
    $order = Order::query()
        ->where('tenant_id', $data['tenant_id'])
        ->where('number', $data['number'])
        ->first();

    $legacyKinds = [
        OrderCatalog::GROUP_SALE,
        OrderCatalog::GROUP_PURCHASE,
        OrderCatalog::GROUP_SERVICE,
    ];

    $incomingKind = $data['kind'] ?? null;
    $incomingGroup = $data['group'] ?? null;

    $resolvedGroup = $incomingGroup;
    $resolvedKind = $incomingKind;

    if ($resolvedGroup === null && is_string($incomingKind) && in_array($incomingKind, $legacyKinds, true)) {
        $resolvedGroup = $incomingKind;
        $resolvedKind = OrderCatalog::KIND_STANDARD;
    }

    $resolvedGroup ??= OrderCatalog::GROUP_SALE;
    $resolvedKind ??= OrderCatalog::KIND_STANDARD;

    $payload = [
        'party_id' => $data['party_id'],
        'group' => $resolvedGroup,
        'kind' => $resolvedKind,
        'status' => $data['status'],
        'ordered_at' => $data['ordered_at'],
        'notes' => $data['notes'],
        'created_by' => $data['created_by'],
        'updated_by' => $data['updated_by'],
    ];

    if ($order) {
        $order->update($payload);

        return $order;
    }

    return Order::create(array_merge([
        'tenant_id' => $data['tenant_id'],
        'number' => $data['number'],
    ], $payload));
}

    private function replaceOrderItems(string $tenantId, int $orderId, array $items): void
    {
        DB::table('order_items')
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->delete();

        foreach ($items as $index => $item) {
            DB::table('order_items')->insert([
                'tenant_id' => $tenantId,
                'order_id' => $orderId,
                'product_id' => $item['product']?->id,
                'position' => $index + 1,
                'kind' => $item['kind'] ?? ($item['product']?->kind ?? 'product'),
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }
    }
}
