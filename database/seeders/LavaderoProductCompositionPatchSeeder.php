<?php

// FILE: database/seeders/LavaderoProductCompositionPatchSeeder.php | V1

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LavaderoProductCompositionPatchSeeder extends Seeder
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

        $tiempo = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('sku', 'LAV-TIEMPO-SEG')
            ->firstOrFail();

        if ($ficha->tenant_id !== $tiempo->tenant_id) {
            throw new \RuntimeException('El producto compuesto y el componente deben pertenecer al mismo tenant.');
        }

        if ((int) $ficha->id === (int) $tiempo->id) {
            throw new \RuntimeException('Un producto no puede componerse a sí mismo.');
        }

        ProductComponent::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'product_id' => $ficha->id,
                'component_product_id' => $tiempo->id,
            ],
            [
                'quantity' => 300,
                'unit_label' => 'segundo',
                'is_required' => true,
                'sort_order' => 1,
                'metadata' => [
                    'seed' => [
                        'source' => 'LavaderoProductCompositionPatchSeeder',
                        'description' => 'Ficha lavado 5 minutos compuesta por 300 segundos.',
                    ],
                ],
            ]
        );
    }
}