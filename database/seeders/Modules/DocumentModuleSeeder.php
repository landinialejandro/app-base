<?php

// FILE: database/seeders/Modules/DocumentModuleSeeder.php | V3

namespace Database\Seeders\Modules;

use App\Events\OperationalRecordCreated;
use App\Events\OperationalRecordUpdated;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Support\Catalogs\DocumentCatalog;
use Illuminate\Support\Facades\DB;

class DocumentModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (
            ! $this->hasDependency('tenants')
            || ! $this->hasDependency('users')
            || ! $this->hasDependency('parties')
            || ! $this->hasDependency('orders')
            || ! $this->hasDependency('products')
        ) {
            throw new \RuntimeException('DocumentModuleSeeder requires tenants, users, parties, orders, and products');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $parties = $this->getDependency('parties');
        $orders = $this->getDependency('orders');
        $products = $this->getDependency('products');

        $this->createDocumentSequences($tenants['tech']);
        $this->createDocumentSequences($tenants['andina']);

        $documents = [];
        $documents['tech'] = $this->createTechDocuments(
            $tenants['tech'],
            $users,
            $parties['techFixed'],
            $orders['tech'],
            $products['tech']
        );

        $documents['andina'] = $this->createAndinaDocuments(
            $tenants['andina'],
            $users,
            $parties['andinaFixed'],
            $orders['andina'],
            $products['andina']
        );

        $this->context['documents'] = $documents;
    }

    private function createDocumentSequences($tenant): void
    {
        $pointOfSale = '0001';

        $definitions = [
            ['doc_type' => 'quote', 'prefix' => 'PRE', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'delivery_note', 'prefix' => 'REM', 'padding' => 8, 'next_number' => 1],
            ['doc_type' => 'invoice', 'prefix' => 'FAC', 'padding' => 8, 'next_number' => 1],
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

    private function createTechDocuments($tenant, array $users, $parties, $orders, $products)
    {
        $documents = collect();

        $acme = $parties[0] ?? null;
        $laura = $parties[1] ?? null;
        $ownerTech = $users['ownerTech'];
        $techUser = $users['techUser'];

        $documents->push($this->createDocumentWithItems([
            'tenant_id' => $tenant->id,
            'party_id' => $acme?->id,
            'order_id' => $orders[0]?->id,
            'created_by' => $ownerTech->id,
            'updated_by' => $ownerTech->id,
            'group' => DocumentCatalog::GROUP_SALE,
            'kind' => DocumentCatalog::KIND_QUOTE,
            'number' => 'PRE-00000001',
            'status' => DocumentCatalog::STATUS_PENDING_APPROVAL,
            'issued_at' => now()->subDays(2)->toDateString(),
            'due_at' => now()->addDays(10)->toDateString(),
            'currency_code' => 'ARS',
            'notes' => 'Presupuesto demo derivado de orden.',
            'items' => [
                ['product' => $products[0] ?? null, 'description' => 'Aceite 10W40', 'kind' => 'product', 'quantity' => 2, 'unit_price' => 18500],
                ['product' => $products[3] ?? null, 'description' => 'Service general', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 48000],
            ],
        ]));

        $documents->push($this->createDocumentWithItems([
            'tenant_id' => $tenant->id,
            'party_id' => $laura?->id,
            'order_id' => $orders[1]?->id,
            'created_by' => $techUser->id,
            'updated_by' => $techUser->id,
            'group' => DocumentCatalog::GROUP_SERVICE,
            'kind' => DocumentCatalog::KIND_DELIVERY_NOTE,
            'number' => 'REM-00000001',
            'status' => DocumentCatalog::STATUS_APPROVED,
            'issued_at' => now()->subDay()->toDateString(),
            'due_at' => null,
            'currency_code' => 'ARS',
            'notes' => 'Remito demo asociado a orden de servicio.',
            'items' => [
                ['product' => $products[2] ?? null, 'description' => 'Kit transmisión', 'kind' => 'product', 'quantity' => 1, 'unit_price' => 69000],
                ['product' => $products[4] ?? null, 'description' => 'Diagnóstico', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 22000],
            ],
        ]));

        return $documents;
    }

    private function createAndinaDocuments($tenant, array $users, $parties, $orders, $products)
    {
        $documents = collect();

        $obrasPatagonicas = $parties[0] ?? null;
        $marcos = $parties[1] ?? null;
        $ownerAndina = $users['ownerAndina'];
        $andinaUser = $users['andinaUser'];

        $documents->push($this->createDocumentWithItems([
            'tenant_id' => $tenant->id,
            'party_id' => $obrasPatagonicas?->id,
            'order_id' => $orders[0]?->id,
            'created_by' => $ownerAndina->id,
            'updated_by' => $ownerAndina->id,
            'group' => DocumentCatalog::GROUP_SALE,
            'kind' => DocumentCatalog::KIND_QUOTE,
            'number' => 'PRE-00000002',
            'status' => DocumentCatalog::STATUS_PENDING_APPROVAL,
            'issued_at' => now()->subDays(3)->toDateString(),
            'due_at' => now()->addDays(12)->toDateString(),
            'currency_code' => 'ARS',
            'notes' => 'Presupuesto demo con materiales.',
            'items' => [
                ['product' => $products[0] ?? null, 'description' => 'Hormigón H21', 'kind' => 'product', 'quantity' => 8, 'unit_price' => 125000],
                ['product' => $products[1] ?? null, 'description' => 'Hierro 8mm', 'kind' => 'product', 'quantity' => 30, 'unit_price' => 18500],
            ],
        ]));

        $documents->push($this->createDocumentWithItems([
            'tenant_id' => $tenant->id,
            'party_id' => $marcos?->id,
            'order_id' => $orders[1]?->id,
            'created_by' => $andinaUser->id,
            'updated_by' => $andinaUser->id,
            'group' => DocumentCatalog::GROUP_SERVICE,
            'kind' => DocumentCatalog::KIND_INVOICE,
            'number' => 'FAC-00000001',
            'status' => DocumentCatalog::STATUS_APPROVED,
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays(15)->toDateString(),
            'currency_code' => 'ARS',
            'notes' => 'Factura demo por servicios.',
            'items' => [
                ['product' => $products[2] ?? null, 'description' => 'Servicio topográfico', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 150000],
                ['product' => $products[3] ?? null, 'description' => 'Inspección técnica', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 98000],
            ],
        ]));

        return $documents;
    }

    private function createDocumentWithItems(array $data): Document
    {
        $subtotal = 0;

        $normalizedItems = collect($data['items'])->map(function ($item, $index) use (&$subtotal) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $lineTotal = round($quantity * $unitPrice, 2);

            $subtotal += $lineTotal;

            return [
                'product_id' => $item['product']?->id,
                'position' => $index + 1,
                'kind' => $item['kind'],
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        });

        $taxTotal = 0;
        $total = $subtotal + $taxTotal;

        $targetStatus = $data['status'];

        if (! DocumentCatalog::isValidStatus($targetStatus)) {
            throw new \RuntimeException("Invalid document status [{$targetStatus}] for document seed [{$data['number']}].");
        }

        $document = Document::query()
            ->where('tenant_id', $data['tenant_id'])
            ->where('number', $data['number'])
            ->first();

        $payload = [
            'tenant_id' => $data['tenant_id'],
            'party_id' => $data['party_id'],
            'order_id' => $data['order_id'],
            'group' => $data['group'],
            'kind' => $data['kind'],
            'number' => $data['number'],
            'status' => DocumentCatalog::STATUS_DRAFT,
            'issued_at' => $data['issued_at'],
            'due_at' => $data['due_at'],
            'currency_code' => $data['currency_code'],
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'notes' => $data['notes'],
            'created_by' => $data['created_by'],
            'updated_by' => $data['updated_by'],
        ];

        if ($document) {
            $beforeAttributes = $document->getAttributes();

            $document->update($payload);

            event(new OperationalRecordUpdated(
                record: $document,
                beforeAttributes: $beforeAttributes,
                actorUserId: $data['updated_by'] ?? null,
            ));

            $document->items()->delete();
        } else {
            $document = Document::create($payload);

            event(new OperationalRecordCreated(
                record: $document,
                actorUserId: $data['created_by'] ?? null,
            ));
        }

        foreach ($normalizedItems as $item) {
            DocumentItem::create([
                'tenant_id' => $data['tenant_id'],
                'document_id' => $document->id,
                'product_id' => $item['product_id'],
                'position' => $item['position'],
                'kind' => $item['kind'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'status' => 'pending',
                'unit_price' => $item['unit_price'],
                'line_total' => $item['line_total'],
            ]);
        }

        $document = $document->fresh('items');

        if ($targetStatus !== DocumentCatalog::STATUS_DRAFT) {
            $this->transitionDocumentStatus(
                document: $document,
                status: DocumentCatalog::STATUS_PENDING_APPROVAL,
                actorUserId: $data['updated_by'] ?? null
            );

            if ($targetStatus === DocumentCatalog::STATUS_APPROVED) {
                $this->transitionDocumentStatus(
                    document: $document->fresh(),
                    status: DocumentCatalog::STATUS_APPROVED,
                    actorUserId: $data['updated_by'] ?? null
                );
            }
        }

        return $document->fresh('items');
    }

    private function transitionDocumentStatus(Document $document, string $status, int|string|null $actorUserId = null): Document
    {
        $document->refresh();

        if (! DocumentCatalog::canTransition($document->status, $status)) {
            throw new \RuntimeException("Invalid document status transition [{$document->status}] -> [{$status}] for document [{$document->number}].");
        }

        $beforeAttributes = $document->getAttributes();

        $document->update([
            'status' => $status,
            'updated_by' => $actorUserId,
        ]);

        event(new OperationalRecordUpdated(
            record: $document,
            beforeAttributes: $beforeAttributes,
            actorUserId: $actorUserId !== null ? (int) $actorUserId : null,
        ));

        return $document->fresh();
    }
}