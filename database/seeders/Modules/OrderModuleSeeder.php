<?php

// database/seeders/Modules/OrderModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants') || ! $this->hasDependency('users') || ! $this->hasDependency('parties') || ! $this->hasDependency('products')) {
            throw new \RuntimeException('OrderModuleSeeder requires tenants, users, parties, and products');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $parties = $this->getDependency('parties');
        $products = $this->getDependency('products');
        $orders = [];

        // Create Tech orders
        $techOrders = $this->createTechOrders(
            $tenants['tech'],
            $users,
            $parties['techFixed'],
            $products['tech']
        );

        // Create Andina orders
        $andinaOrders = $this->createAndinaOrders(
            $tenants['andina'],
            $users,
            $parties['andinaFixed'],
            $products['andina']
        );

        $orders['tech'] = $techOrders;
        $orders['andina'] = $andinaOrders;

        $this->context['orders'] = $orders;
    }

    private function createTechOrders($tenant, array $users, $parties, $products): Collection
    {
        $orders = collect();
        $acme = $parties[0] ?? null;
        $laura = $parties[1] ?? null;

        // Order 1: Sale to ACME
        $order1 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $acme?->id,
            'created_by' => $users['ownerTech']->id,
            'updated_by' => $users['ownerTech']->id,
            'kind' => 'sale',
            'number' => 'TECH-ORD-0001',
            'status' => 'draft',
            'ordered_at' => now()->subDays(5)->toDateString(),
            'notes' => 'Pedido inicial de cliente estratégico.',
        ]);

        $this->createOrderItems($tenant->id, $order1->id, [
            ['product' => $products[0], 'description' => 'Aceite 10W40', 'quantity' => 2, 'unit_price' => 18500],
            ['product' => $products[1], 'description' => 'Filtro de aceite', 'quantity' => 1, 'unit_price' => 8500],
            ['product' => $products[3], 'kind' => 'service', 'description' => 'Service general', 'quantity' => 1, 'unit_price' => 48000],
        ]);
        $orders->push($order1);

        // Order 2: Service to Laura
        $order2 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $laura?->id,
            'created_by' => $users['techUser']->id,
            'updated_by' => $users['techUser']->id,
            'kind' => 'service',
            'number' => 'TECH-ORD-0002',
            'status' => 'confirmed',
            'ordered_at' => now()->subDays(2)->toDateString(),
            'notes' => 'Trabajo técnico confirmado.',
        ]);

        $this->createOrderItems($tenant->id, $order2->id, [
            ['product' => $products[2], 'description' => 'Kit transmisión', 'quantity' => 1, 'unit_price' => 69000],
            ['product' => $products[4], 'description' => 'Diagnóstico', 'quantity' => 1, 'unit_price' => 22000],
        ]);
        $orders->push($order2);

        // Order 3: Purchase (cancelled)
        $order3 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $acme?->id,
            'created_by' => $users['shared']->id,
            'updated_by' => $users['shared']->id,
            'kind' => 'purchase',
            'number' => 'TECH-ORD-0003',
            'status' => 'cancelled',
            'ordered_at' => now()->subDays(1)->toDateString(),
            'notes' => 'Compra demo cancelada para probar estados.',
        ]);

        $this->createOrderItems($tenant->id, $order3->id, [
            ['product' => $products[1], 'description' => 'Filtro de aceite', 'quantity' => 4, 'unit_price' => 8500],
            ['product' => $products[2], 'description' => 'Kit transmisión', 'quantity' => 1, 'unit_price' => 69000],
        ]);
        $orders->push($order3);

        return $orders;
    }

    private function createAndinaOrders($tenant, array $users, $parties, $products): Collection
    {
        $orders = collect();
        $obrasPatagonicas = $parties[0] ?? null;
        $marcos = $parties[1] ?? null;

        // Order 1: Sale to Obras Patagónicas
        $order1 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $obrasPatagonicas?->id,
            'created_by' => $users['ownerAndina']->id,
            'updated_by' => $users['ownerAndina']->id,
            'kind' => 'sale',
            'number' => 'AND-ORD-0001',
            'status' => 'draft',
            'ordered_at' => now()->subDays(6)->toDateString(),
            'notes' => 'Materiales para avance de obra.',
        ]);

        $this->createOrderItems($tenant->id, $order1->id, [
            ['product' => $products[0], 'description' => 'Hormigón H21', 'quantity' => 8, 'unit_price' => 125000],
            ['product' => $products[1], 'description' => 'Hierro 8mm', 'quantity' => 30, 'unit_price' => 18500],
        ]);
        $orders->push($order1);

        // Order 2: Service to Marcos
        $order2 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $marcos?->id,
            'created_by' => $users['andinaUser']->id,
            'updated_by' => $users['andinaUser']->id,
            'kind' => 'service',
            'number' => 'AND-ORD-0002',
            'status' => 'confirmed',
            'ordered_at' => now()->subDay()->toDateString(),
            'notes' => 'Servicios técnicos programados.',
        ]);

        $this->createOrderItems($tenant->id, $order2->id, [
            ['product' => $products[2], 'description' => 'Servicio topográfico', 'quantity' => 1, 'unit_price' => 150000],
            ['product' => $products[3], 'description' => 'Inspección técnica', 'quantity' => 1, 'unit_price' => 98000],
        ]);
        $orders->push($order2);

        // Order 3: Purchase (cancelled)
        $order3 = $this->createOrder([
            'tenant_id' => $tenant->id,
            'party_id' => $obrasPatagonicas?->id,
            'created_by' => $users['shared']->id,
            'updated_by' => $users['shared']->id,
            'kind' => 'purchase',
            'number' => 'AND-ORD-0003',
            'status' => 'cancelled',
            'ordered_at' => now()->subDays(3)->toDateString(),
            'notes' => 'Compra demo cancelada.',
        ]);

        $this->createOrderItems($tenant->id, $order3->id, [
            ['product' => $products[1], 'description' => 'Hierro 8mm', 'quantity' => 10, 'unit_price' => 18500],
        ]);
        $orders->push($order3);

        return $orders;
    }

    private function createOrder(array $data): Order
    {
        return Order::firstOrCreate(
            [
                'tenant_id' => $data['tenant_id'],
                'number' => $data['number'],
            ],
            [
                'party_id' => $data['party_id'],
                'kind' => $data['kind'],
                'status' => $data['status'],
                'ordered_at' => $data['ordered_at'],
                'notes' => $data['notes'],
                'created_by' => $data['created_by'],
                'updated_by' => $data['updated_by'],
            ]
        );
    }

    private function createOrderItems(string $tenantId, int $orderId, array $items): void
    {
        if (DB::table('order_items')->where('tenant_id', $tenantId)->where('order_id', $orderId)->exists()) {
            return;
        }

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
