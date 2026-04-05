<?php

// FILE: database/seeders/Modules/ProductModuleSeeder.php | V2

namespace Database\Seeders\Modules;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants')) {
            throw new \RuntimeException('ProductModuleSeeder requires tenants');
        }

        $tenants = $this->getDependency('tenants');
        $products = [];

        $products['tech'] = $this->createProducts(
            tenantId: $tenants['tech']->id,
            productsData: [
                [
                    'name' => 'Aceite 10W40',
                    'sku' => 'ACE-10W40',
                    'kind' => 'product',
                    'unit_label' => 'litro',
                    'price' => 18500,
                    'description' => 'Lubricante para service.',
                ],
                [
                    'name' => 'Filtro de aceite',
                    'sku' => 'FILT-001',
                    'kind' => 'product',
                    'unit_label' => 'unidad',
                    'price' => 8500,
                    'description' => 'Repuesto estándar.',
                ],
                [
                    'name' => 'Kit transmisión',
                    'sku' => 'KIT-TR-01',
                    'kind' => 'product',
                    'unit_label' => 'unidad',
                    'price' => 69000,
                    'description' => 'Kit completo.',
                ],
                [
                    'name' => 'Service general',
                    'sku' => 'SERV-GRAL',
                    'kind' => 'service',
                    'unit_label' => 'servicio',
                    'price' => 48000,
                    'description' => 'Mano de obra completa.',
                ],
                [
                    'name' => 'Diagnóstico',
                    'sku' => 'SERV-DIAG',
                    'kind' => 'service',
                    'unit_label' => 'servicio',
                    'price' => 22000,
                    'description' => 'Diagnóstico general.',
                ],
            ]
        );

        $products['andina'] = $this->createProducts(
            tenantId: $tenants['andina']->id,
            productsData: [
                [
                    'name' => 'Hormigón H21',
                    'sku' => 'H21-001',
                    'kind' => 'product',
                    'unit_label' => 'm3',
                    'price' => 125000,
                    'description' => 'Material de obra.',
                ],
                [
                    'name' => 'Hierro 8mm',
                    'sku' => 'HIER-8',
                    'kind' => 'product',
                    'unit_label' => 'barra',
                    'price' => 18500,
                    'description' => 'Hierro para estructura.',
                ],
                [
                    'name' => 'Servicio topográfico',
                    'sku' => 'SERV-TOPO',
                    'kind' => 'service',
                    'unit_label' => 'servicio',
                    'price' => 150000,
                    'description' => 'Relevamiento topográfico.',
                ],
                [
                    'name' => 'Inspección técnica',
                    'sku' => 'SERV-INSP',
                    'kind' => 'service',
                    'unit_label' => 'servicio',
                    'price' => 98000,
                    'description' => 'Inspección y control.',
                ],
                [
                    'name' => 'Movimiento de suelo',
                    'sku' => 'SERV-SUELO',
                    'kind' => 'service',
                    'unit_label' => 'jornada',
                    'price' => 210000,
                    'description' => 'Trabajo con maquinaria.',
                ],
            ]
        );

        $this->context['products'] = $products;
    }

    private function createProducts(string $tenantId, array $productsData): Collection
    {
        $created = collect();

        foreach ($productsData as $data) {
            $created->push(
                Product::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'sku' => $data['sku'],
                    ],
                    [
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'price' => $data['price'],
                        'kind' => $data['kind'],
                        'unit_label' => $data['unit_label'],
                        'is_active' => true,
                    ]
                )
            );
        }

        return $created->values();
    }
}
