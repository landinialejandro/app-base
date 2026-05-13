<?php

// FILE: database/seeders/LavaderoProductCompositionDemoSeeder.php | V3

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LavaderoProductCompositionDemoSeeder extends Seeder
{
public function run(): void
    {
        $tenant = Tenant::query()
            ->where('slug', 'lavadero-sa')
            ->firstOrFail();

        $ficha = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('sku', 'LAV-FICHA')
            ->firstOrFail();

        $components = [
            [
                'sku' => 'LAV-TIEMPO-SEG',
                'quantity' => 300,
                'unit_label' => 'segundo',
                'sort_order' => 1,
                'description' => 'Ficha lavado 5 minutos compuesta por 300 segundos.',
            ],
            [
                'sku' => 'LAV-PROD-FRANELA',
                'quantity' => 1,
                'unit_label' => 'unidad',
                'sort_order' => 2,
                'description' => 'Consumo físico demo de franela asociado a la producción de fichas.',
            ],
            [
                'sku' => 'LAV-PROD-SILICONA',
                'quantity' => 1,
                'unit_label' => 'unidad',
                'sort_order' => 3,
                'description' => 'Consumo físico demo de silicona asociado a la producción de fichas.',
            ],
        ];

        foreach ($components as $componentDefinition) {
            $componentProduct = Product::query()
                ->where('tenant_id', $tenant->id)
                ->where('sku', $componentDefinition['sku'])
                ->firstOrFail();

            if ($ficha->tenant_id !== $componentProduct->tenant_id) {
                throw new \RuntimeException('Producción demo: producto compuesto y componente deben pertenecer al mismo tenant.');
            }

            if ((int) $ficha->id === (int) $componentProduct->id) {
                throw new \RuntimeException('Un producto no puede componerse a sí mismo.');
            }

            ProductComponent::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'product_id' => $ficha->id,
                    'component_product_id' => $componentProduct->id,
                ],
                [
                    'quantity' => $componentDefinition['quantity'],
                    'unit_label' => $componentDefinition['unit_label'],
                    'is_required' => true,
                    'sort_order' => $componentDefinition['sort_order'],
                    'metadata' => [
                        'seed' => [
                            'source' => 'LavaderoProductCompositionDemoSeeder',
                            'description' => $componentDefinition['description'],
                        ],
                    ],
                ]
            );
        }
    }
}