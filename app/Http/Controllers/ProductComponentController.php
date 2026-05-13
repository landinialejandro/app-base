<?php

// FILE: app/Http/Controllers/ProductComponentController.php | V2

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductComponent;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\ProductNavigationTrail;
use App\Support\Products\ProductLineItemSelector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductComponentController extends Controller
{
    public function create(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $productComponent = new ProductComponent([
            'quantity' => 1,
            'is_required' => true,
            'sort_order' => ((int) $product->components()->max('sort_order')) + 1,
        ]);

        $componentOptions = $this->componentOptions($product);
        $navigationTrail = ProductNavigationTrail::componentCreate($request, $product);

        return view('products.composition.create', compact(
            'product',
            'productComponent',
            'componentOptions',
            'navigationTrail',
        ));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $productSelector = app(ProductLineItemSelector::class);

        $data = $request->validate($this->storeRules($product));

        $productSelector->assertViewable(
            user: auth()->user(),
            tenantId: (string) $product->tenant_id,
            productId: $data['component_product_id'],
            enabled: true,
        );

        $this->abortIfInvalidComponentProduct($product, (int) $data['component_product_id']);

        $existingComponent = ProductComponent::withTrashed()
            ->where('tenant_id', $product->tenant_id)
            ->where('product_id', $product->id)
            ->where('component_product_id', $data['component_product_id'])
            ->first();

        if ($existingComponent && ! $existingComponent->trashed()) {
            return back()
                ->withErrors([
                    'component_product_id' => 'Este componente ya forma parte de la composición del producto.',
                ])
                ->withInput();
        }

        $payload = [
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'component_product_id' => $data['component_product_id'],
            'quantity' => $data['quantity'],
            'unit_label' => $data['unit_label'] ?? null,
            'is_required' => $request->boolean('is_required', true),
            'sort_order' => $data['sort_order'] ?? (((int) $product->components()->max('sort_order')) + 1),
            'metadata' => null,
        ];

        if ($existingComponent && $existingComponent->trashed()) {
            $existingComponent->restore();
            $existingComponent->update($payload);
        } else {
            ProductComponent::create($payload);
        }

        return $this->redirectToProduct($request, $product, 'Componente agregado correctamente.');
    }

    public function edit(Request $request, Product $product, ProductComponent $component)
    {
        $this->authorize('update', $product);

        $this->abortUnlessBelongsToProduct($product, $component);

        $productComponent = $component;
        $componentOptions = $this->componentOptions($product);
        $navigationTrail = ProductNavigationTrail::componentEdit($request, $product, $component);

        return view('products.composition.edit', compact(
            'product',
            'productComponent',
            'componentOptions',
            'navigationTrail',
        ));
    }

    public function update(Request $request, Product $product, ProductComponent $component): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->abortUnlessBelongsToProduct($product, $component);

        $productSelector = app(ProductLineItemSelector::class);

        $data = $request->validate($this->updateRules($product, $component));

        $productSelector->assertViewable(
            user: auth()->user(),
            tenantId: (string) $product->tenant_id,
            productId: $data['component_product_id'],
            enabled: true,
        );

        $this->abortIfInvalidComponentProduct($product, (int) $data['component_product_id']);

        $component->update([
            'component_product_id' => $data['component_product_id'],
            'quantity' => $data['quantity'],
            'unit_label' => $data['unit_label'] ?? null,
            'is_required' => $request->boolean('is_required'),
            'sort_order' => $data['sort_order'] ?? $component->sort_order,
        ]);

        return $this->redirectToProduct($request, $product, 'Componente actualizado correctamente.');
    }

    public function destroy(Request $request, Product $product, ProductComponent $component): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->abortUnlessBelongsToProduct($product, $component);

        $component->delete();

        return $this->redirectToProduct($request, $product, 'Componente eliminado correctamente.');
    }

    private function componentOptions(Product $product)
    {
        return app(ProductLineItemSelector::class)
            ->optionsFor(
                user: auth()->user(),
                tenantId: (string) $product->tenant_id,
                enabled: true,
            )
            ->reject(fn (Product $option) => (int) $option->id === (int) $product->id)
            ->values();
    }

    private function storeRules(Product $product): array
    {
        return [
            'component_product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($product) {
                    $query->where('tenant_id', $product->tenant_id)
                        ->whereNull('deleted_at');
                }),
            ],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_label' => ['nullable', 'string', 'max:50'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }

    private function updateRules(Product $product, ProductComponent $component): array
    {
        return [
            'component_product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($product) {
                    $query->where('tenant_id', $product->tenant_id)
                        ->whereNull('deleted_at');
                }),
                Rule::unique('product_components', 'component_product_id')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $product->tenant_id)
                        ->where('product_id', $product->id))
                    ->ignore($component->id),
            ],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_label' => ['nullable', 'string', 'max:50'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }

    private function abortUnlessBelongsToProduct(Product $product, ProductComponent $component): void
    {
        abort_unless((int) $component->product_id === (int) $product->id, 404);
        abort_unless((string) $component->tenant_id === (string) $product->tenant_id, 404);
    }

    private function abortIfInvalidComponentProduct(Product $product, int $componentProductId): void
    {
        abort_if(
            (int) $product->id === $componentProductId,
            422,
            'Un producto no puede componerse a sí mismo.'
        );
    }

private function redirectToProduct(Request $request, Product $product, string $message): RedirectResponse
    {
        $navigationTrail = ProductNavigationTrail::show($request, $product);

        return redirect()
            ->route('products.show', [
                'product' => $product,
                'return_tab' => 'product.composition.items',
            ] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', $message);
    }
}
