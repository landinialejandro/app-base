<?php

// database/seeders/Modules/ProductModuleSeeder.php

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

        $products['tech'] = $this->createTechProducts($tenants['tech']->id);
        $products['andina'] = $this->createAndinaProducts($tenants['andina']->id);

        $this->context['products'] = $products;
    }

    private function createTechProducts(string $tenantId): Collection
    {
        $products = [
            ['name' => 'Aceite 10W40', 'sku' => 'ACE-10W40', 'kind' => 'product', 'unit_label' => 'litro', 'price' => 18500, 'description' => 'Lubricante para service.'],
            ['name' => 'Filtro de aceite', 'sku' => 'FILT-001', 'kind' => 'product', 'unit_label' => 'unidad', 'price' => 8500, 'description' => 'Repuesto estándar.'],
            ['name' => 'Kit transmisión', 'sku' => 'KIT-TR-01', 'kind' => 'product', 'unit_label' => 'unidad', 'price' => 69000, 'description' => 'Kit completo.'],
            ['name' => 'Service general', 'sku' => 'SERV-GRAL', 'kind' => 'service', 'unit_label' => 'servicio', 'price' => 48000, 'description' => 'Mano de obra completa.'],
            ['name' => 'Diagnóstico', 'sku' => 'SERV-DIAG', 'kind' => 'service', 'unit_label' => 'servicio', 'price' => 22000, 'description' => 'Diagnóstico general.'],
        ];

        return $this->createProducts($tenantId, $products);
    }

    private function createAndinaProducts(string $tenantId): Collection
    {
        $products = [
            ['name' => 'Hormigón H21', 'sku' => 'H21-001', 'kind' => 'product', 'unit_label' => 'm3', 'price' => 125000, 'description' => 'Material de obra.'],
            ['name' => 'Hierro 8mm', 'sku' => 'HIER-8', 'kind' => 'product', 'unit_label' => 'barra', 'price' => 18500, 'description' => 'Hierro para estructura.'],
            ['name' => 'Servicio topográfico', 'sku' => 'SERV-TOPO', 'kind' => 'service', 'unit_label' => 'servicio', 'price' => 150000, 'description' => 'Relevamiento topográfico.'],
            ['name' => 'Inspección técnica', 'sku' => 'SERV-INSP', 'kind' => 'service', 'unit_label' => 'servicio', 'price' => 98000, 'description' => 'Inspección y control.'],
            ['name' => 'Movimiento de suelo', 'sku' => 'SERV-SUELO', 'kind' => 'service', 'unit_label' => 'jornada', 'price' => 210000, 'description' => 'Trabajo con maquinaria.'],
        ];

        return $this->createProducts($tenantId, $products);
    }

    private function createProducts(string $tenantId, array $productsData): Collection
    {
        $created = collect();

        foreach ($productsData as $data) {
            $created->push(Product::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $data['name']],
                array_merge($data, ['is_active' => true])
            ));
        }

        return $created;
    }
}
