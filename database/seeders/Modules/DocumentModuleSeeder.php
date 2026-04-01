<?php

// database/seeders/Modules/DocumentModuleSeeder.php

namespace Database\Seeders\Modules;

use Illuminate\Support\Facades\DB;

class DocumentModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants') || ! $this->hasDependency('users') || ! $this->hasDependency('parties') || ! $this->hasDependency('orders') || ! $this->hasDependency('products')) {
            throw new \RuntimeException('DocumentModuleSeeder requires tenants, users, parties, orders, and products');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $parties = $this->getDependency('parties');
        $orders = $this->getDependency('orders');
        $products = $this->getDependency('products');

        // Create document sequences first
        $this->createDocumentSequences($tenants['tech']);
        $this->createDocumentSequences($tenants['andina']);

        // Create Tech documents
        $this->createTechDocuments($tenants['tech'], $users, $parties['techFixed'], $orders['tech'], $products['tech']);

        // Create Andina documents
        $this->createAndinaDocuments($tenants['andina'], $users, $parties['andinaFixed'], $orders['andina'], $products['andina']);
    }

    private function createDocumentSequences($tenant): void
    {
        $pointOfSale = '0001';

        $definitions = [
            ['doc_type' => 'quote', 'prefix' => 'PRE', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'delivery_note', 'prefix' => 'REM', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'invoice', 'prefix' => 'FAC', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'work_order', 'prefix' => 'OTR', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'receipt', 'prefix' => 'REC', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'credit_note', 'prefix' => 'NCR', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'order.sale', 'prefix' => 'ORD', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'order.purchase', 'prefix' => 'OCO', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'order.service', 'prefix' => 'OSE', 'padding' => 8, 'next_number' => 1],
        ];

        foreach ($definitions as $definition) {
            DB::table('document_sequences')->updateOrInsert(
                [
                    'tenant_id' => $tenant->id,
                    'doc_type' => $definition['doc_type'],
                    'point_of_sale' => $pointOfSale,
                ],
                [
                    'branch_id' => null,
                    'prefix' => $definition['prefix'],
                    'padding' => $definition['padding'],
                    'next_number' => $definition['next_number'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function createTechDocuments($tenant, array $users, $parties, $orders, $products): void
    {
        $acme = $parties[0] ?? null;
        $laura = $parties[1] ?? null;
        $ownerTech = $users['ownerTech'];
        $techUser = $users['techUser'];
        $sharedUser = $users['shared'];

        // Quote from order
        $this->createDocumentWithItems([
            'tenant_id' => $tenant->id,
            'party_id' => $acme?->id,
            'order_id' => $orders[0]?->id,
            'created_by' => $ownerTech->id,
            'updated_by' => $ownerTech->id,
            'kind' => 'quote',
            'number' => 'PRE-00000001',
            'status' => 'draft',
            'issued_at' => now()->subDays(5)->toDateString(),
            'due_at' => now()->addDays(10)->toDateString(),
            'currency_code' => 'ARS',
            'notes' => 'Presupuesto asociado a la orden.',
            'items' => [
                ['product' => $products[0], 'description' => 'Aceite 10W40', 'kind' => 'product', 'quantity' => 2, 'unit_price' => 18500],
                ['product' => $products[3], 'description' => 'Service general', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 48000],
            ],
        ]);

        // Work order
        $this->createDocumentWithItems([
            'tenant_id' => $tenant->id,
            'party_id' => $laura?->id,
            'order_id' => $orders[1]?->id,
            'created_by' => $techUser->id,
            'updated_by' => $techUser->id,
            'kind' => 'work_order',
            'number' => 'OTR-00000001',
            'status' => 'draft',
            'issued_at' => now()->subDays(1)->toDateString(),
            'due_at' => now()->addDays(3)->toDateString(),
            'currency_code' => 'ARS',
            'notes' => 'Orden de trabajo generada desde pedido confirmado.',
            'items' => [
                ['product' => $products[2], 'description' => 'Kit transmisión', 'kind' => 'product', 'quantity' => 1, 'unit_price' => 69000],
                ['product' => $products[4], 'description' => 'Diagnóstico', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 22000],
            ],
        ]);

        // Independent receipt
        $this->createDocumentWithItems([
            'tenant_id' => $tenant->id,
            'party_id' => $acme?->id,
            'order_id' => null,
            'created_by' => $sharedUser->id,
            'updated_by' => $sharedUser->id,
            'kind' => 'receipt',
            'number' => 'REC-00000001',
            'status' => 'issued',
            'issued_at' => now()->toDateString(),
            'due_at' => null,
            'currency_code' => 'ARS',
            'notes' => 'Recibo demo independiente.',
            'items' => [
                ['product' => $products[1], 'description' => 'Filtro de aceite', 'kind' => 'product', 'quantity' => 2, 'unit_price' => 8500],
            ],
        ]);
    }

    private function createAndinaDocuments($tenant, array $users, $parties, $orders, $products): void
    {
        $obrasPatagonicas = $parties[0] ?? null;
        $marcos = $parties[1] ?? null;
        $ownerAndina = $users['ownerAndina'];
        $andinaUser = $users['andinaUser'];

        // Invoice
        $this->createDocumentWithItems([
            'tenant_id' => $tenant->id,
            'party_id' => $obrasPatagonicas?->id,
            'order_id' => $orders[0]?->id,
            'created_by' => $ownerAndina->id,
            'updated_by' => $ownerAndina->id,
            'kind' => 'invoice',
            'number' => 'FAC-00000001',
            'status' => 'draft',
            'issued_at' => now()->subDays(4)->toDateString(),
            'due_at' => now()->addDays(15)->toDateString(),
            'currency_code' => 'ARS',
            'notes' => 'Factura demo con materiales.',
            'items' => [
                ['product' => $products[0], 'description' => 'Hormigón H21', 'kind' => 'product', 'quantity' => 8, 'unit_price' => 125000],
                ['product' => $products[1], 'description' => 'Hierro 8mm', 'kind' => 'product', 'quantity' => 30, 'unit_price' => 18500],
            ],
        ]);

        // Receipt for services
        $this->createDocumentWithItems([
            'tenant_id' => $tenant->id,
            'party_id' => $marcos?->id,
            'order_id' => $orders[1]?->id,
            'created_by' => $andinaUser->id,
            'updated_by' => $andinaUser->id,
            'kind' => 'receipt',
            'number' => 'REC-00000002',
            'status' => 'issued',
            'issued_at' => now()->toDateString(),
            'due_at' => null,
            'currency_code' => 'ARS',
            'notes' => 'Recibo demo por servicios.',
            'items' => [
                ['product' => $products[2], 'description' => 'Servicio topográfico', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 150000],
                ['product' => $products[3], 'description' => 'Inspección técnica', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 98000],
            ],
        ]);
    }

    private function createDocumentWithItems(array $data): void
    {
        $subtotal = 0;
        $normalizedItems = collect($data['items'])->map(function ($item, $index) use (&$subtotal) {
            $lineTotal = round(((float) $item['quantity']) * ((float) $item['unit_price']), 2);
            $subtotal += $lineTotal;

            return [
                'product_id' => $item['product']?->id,
                'position' => $index + 1,
                'kind' => $item['kind'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => $lineTotal,
            ];
        });

        $taxTotal = 0;
        $total = $subtotal + $taxTotal;

        $document = DB::table('documents')
            ->where('tenant_id', $data['tenant_id'])
            ->where('number', $data['number'])
            ->first();

        if (! $document) {
            $documentId = DB::table('documents')->insertGetId([
                'tenant_id' => $data['tenant_id'],
                'party_id' => $data['party_id'],
                'order_id' => $data['order_id'],
                'kind' => $data['kind'],
                'number' => $data['number'],
                'status' => $data['status'],
                'issued_at' => $data['issued_at'],
                'due_at' => $data['due_at'],
                'currency_code' => $data['currency_code'],
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'notes' => $data['notes'],
                'created_by' => $data['created_by'],
                'updated_by' => $data['updated_by'],
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        } else {
            $documentId = $document->id;
        }

        if (! DB::table('document_items')->where('tenant_id', $data['tenant_id'])->where('document_id', $documentId)->exists()) {
            foreach ($normalizedItems as $item) {
                DB::table('document_items')->insert([
                    'tenant_id' => $data['tenant_id'],
                    'document_id' => $documentId,
                    'product_id' => $item['product_id'],
                    'position' => $item['position'],
                    'kind' => $item['kind'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);
            }
        }
    }
}
