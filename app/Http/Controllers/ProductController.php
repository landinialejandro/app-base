<?php

// FILE: app/Http/Controllers/ProductController.php | V8

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Auth\Security;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\ProductStockCalculator;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\ProductNavigationTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $q = trim((string) $request->get('q', ''));
        $kind = $request->get('kind');
        $isActive = $request->get('is_active');

        $products = app(Security::class)
            ->scope(auth()->user(), 'products.viewAny', Product::query())
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($kind, function ($query) use ($kind) {
                $query->where('kind', $kind);
            })
            ->when($isActive !== null && $isActive !== '', function ($query) use ($isActive) {
                $query->where('is_active', (bool) $isActive);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Product::class);

        $navigationTrail = ProductNavigationTrail::create($request);

        return view('products.create', compact('navigationTrail'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->all();
        $data['is_active'] = $request->boolean('is_active');

        $validated = validator($data, $this->rules())->validate();

        $product = Product::create($validated);
        $navigationTrail = ProductNavigationTrail::show($request, $product);

        return redirect()
            ->route('products.show', ['product' => $product] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Producto creado correctamente.');
    }

    public function show(Request $request, Product $product): View
    {
        $this->authorize('view', $product);

        $product->load([
            'attachments' => fn ($query) => $query->ordered(),
        ]);

        $inventoryMovements = $product->inventoryMovements()
            ->with(['order', 'document'])
            ->latest('id')
            ->get();

        $currentStock = app(ProductStockCalculator::class)->forProduct($product);

        $navigationTrail = ProductNavigationTrail::show($request, $product);

        return view('products.show', compact(
            'product',
            'navigationTrail',
            'inventoryMovements',
            'currentStock',
        ));
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        $navigationTrail = ProductNavigationTrail::edit($request, $product);

        return view('products.edit', compact('product', 'navigationTrail'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->all();
        $data['is_active'] = $request->boolean('is_active');

        $validated = validator($data, $this->rules())->validate();

        $product->update($validated);
        $navigationTrail = ProductNavigationTrail::show($request, $product);

        return redirect()
            ->route('products.show', ['product' => $product] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $navigationTrail = ProductNavigationTrail::show($request, $product);
        $redirectUrl = NavigationTrail::previousUrl($navigationTrail, route('products.index'));

        $product->delete();

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Producto eliminado correctamente.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'kind' => [
                'required',
                Rule::in(ProductCatalog::kinds()),
            ],
            'unit_label' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
