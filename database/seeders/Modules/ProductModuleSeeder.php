<?php

// FILE: database/seeders/Modules/ProductModuleSeeder.php | V3

namespace Database\Seeders\Modules;

use App\Events\OperationalRecordCreated;
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

        $products['lavadero'] = $this->createProducts(
            tenantId: $tenants['lavadero']->id,
            productsData: [
                [
                    'name' => 'Ficha lavado 5 minutos',
                    'sku' => 'LAV-FICHA',
                    'kind' => 'product',
                    'unit_label' => 'ficha',
                    'price' => 1500,
                    'description' => 'Unidad stockeable de derecho de uso para activar un ciclo de lavado autoservicio de 5 minutos.',
                ],
                [
                    'name' => 'Tiempo en segundos',
                    'sku' => 'LAV-TIEMPO-SEG',
                    'kind' => 'service',
                    'unit_label' => 'segundo',
                    'price' => 5,
                    'description' => 'Unidad técnica para parametrizar la duración y valor de los ciclos de lavado.',
                ],
                [
                    'name' => 'Lavado básico exterior',
                    'sku' => 'LAV-SERV-BASICO',
                    'kind' => 'service',
                    'unit_label' => 'servicio',
                    'price' => 8500,
                    'description' => 'Lavado exterior con hidrolavado, shampoo y enjuague final.',
                ],
                [
                    'name' => 'Lavado completo exterior',
                    'sku' => 'LAV-SERV-COMPLETO',
                    'kind' => 'service',
                    'unit_label' => 'servicio',
                    'price' => 14500,
                    'description' => 'Lavado exterior con prelavado, shampoo, cepillado manual y enjuague final.',
                ],
                [
                    'name' => 'Aspirado interior',
                    'sku' => 'LAV-SERV-ASPIRADO',
                    'kind' => 'service',
                    'unit_label' => 'servicio',
                    'price' => 6500,
                    'description' => 'Aspirado interior de cabina, alfombras y baúl.',
                ],
                [
                    'name' => 'Shampoo espumante',
                    'sku' => 'LAV-INS-SHAMPOO',
                    'kind' => 'product',
                    'unit_label' => 'litro',
                    'price' => 4200,
                    'description' => 'Insumo concentrado para lavado exterior con espuma activa.',
                ],
                [
                    'name' => 'Cera líquida',
                    'sku' => 'LAV-INS-CERA',
                    'kind' => 'product',
                    'unit_label' => 'litro',
                    'price' => 5100,
                    'description' => 'Insumo para terminación y protección de carrocería.',
                ],
                [
                    'name' => 'Silicona para neumáticos',
                    'sku' => 'LAV-PROD-SILICONA',
                    'kind' => 'product',
                    'unit_label' => 'unidad',
                    'price' => 3900,
                    'description' => 'Producto para acabado y brillo de neumáticos.',
                ],
                [
                    'name' => 'Franelas de microfibra',
                    'sku' => 'LAV-PROD-FRANELA',
                    'kind' => 'product',
                    'unit_label' => 'unidad',
                    'price' => 2500,
                    'description' => 'Paño de microfibra para secado y limpieza exterior o interior.',
                ],
                [
                    'name' => 'Aromatizador vehicular',
                    'sku' => 'LAV-PROD-AROMA',
                    'kind' => 'product',
                    'unit_label' => 'unidad',
                    'price' => 1800,
                    'description' => 'Aromatizador para venta en mostrador.',
                ],
            ]
        );

        $this->context['products'] = $products;
    }

    private function createProducts(string $tenantId, array $productsData): Collection
    {
        $created = collect();

        foreach ($productsData as $data) {
            $product = Product::updateOrCreate(
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
            );

            if ($product->wasRecentlyCreated) {
                $this->emitProductCreatedActivity($product);
            }

            $created->push($product);
        }

        return $created->values();
    }

    private function emitProductCreatedActivity(Product $product): void
    {
        event(new OperationalRecordCreated(
            record: $product,
            actorUserId: null,
            metadata: [
                'seed' => [
                    'source' => 'ProductModuleSeeder',
                    'sku' => $product->sku,
                    'kind' => $product->kind,
                    'unit_label' => $product->unit_label,
                ],
            ],
        ));
    }
}