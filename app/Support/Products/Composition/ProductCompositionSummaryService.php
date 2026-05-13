<?php

// FILE: app/Support/Products/Composition/ProductCompositionSummaryService.php | V4

namespace App\Support\Products\Composition;

use App\Models\Product;
use App\Models\ProductComponent;
use App\Support\Products\ProductLineItemSelector;
use App\Support\Products\ProductLinked;

class ProductCompositionSummaryService
{
    public function forProduct(Product $product, array $trailQuery = []): array
    {
        $components = $product->components()
            ->with('componentProduct')
            ->get()
            ->filter(fn (ProductComponent $component) => $component->componentProduct !== null)
            ->values()
            ->map(fn (ProductComponent $component) => $this->componentPayload($component, $trailQuery));

        $options = app(ProductLineItemSelector::class)
            ->optionsFor(
                user: auth()->user(),
                tenantId: (string) $product->tenant_id,
                enabled: true,
            )
            ->reject(fn (Product $option) => (int) $option->id === (int) $product->id)
            ->values()
            ->map(fn (Product $option) => [
                'id' => $option->id,
                'name' => $option->name,
                'sku' => $option->sku,
                'kind' => $option->kind,
                'unit_label' => $option->unit_label,
            ]);

        return [
            'can_view' => true,
            'has_components' => $components->isNotEmpty(),
            'components_count' => $components->count(),
            'components' => $components,
            'component_options' => $options,
        ];
    }

    private function componentPayload(ProductComponent $component, array $trailQuery): array
    {
        $componentProduct = $component->componentProduct;

        $linked = ProductLinked::forProduct(
            $componentProduct,
            $trailQuery,
            'Componente',
        );

        return [
            'id' => $component->id,
            'component_product_id' => $component->component_product_id,
            'quantity' => (float) $component->quantity,
            'unit_label' => $component->unit_label ?: $componentProduct?->unit_label,
            'is_required' => (bool) $component->is_required,
            'sort_order' => (int) $component->sort_order,
            'product' => [
                'id' => $componentProduct?->id,
                'name' => $componentProduct?->name ?: 'Componente',
                'sku' => $componentProduct?->sku,
                'kind' => $componentProduct?->kind,
                'unit_label' => $componentProduct?->unit_label,
                'price' => $componentProduct?->price !== null ? (float) $componentProduct->price : null,
                'linked' => $linked,
            ],
        ];
    }
}