<?php

// FILE: app/Http/Controllers/InventoryController.php | V6

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Order;
use App\Models\Product;
use App\Support\Auth\Security;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\InventoryMovementService;
use App\Support\Inventory\ProductStockCalculator;
use App\Support\Navigation\InventoryNavigationTrail;
use App\Support\Navigation\NavigationTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $products = app(Security::class)
            ->scope(auth()->user(), 'products.viewAny', Product::query())
            ->where('kind', ProductCatalog::KIND_PRODUCT)
            ->withCount('inventoryMovements')
            ->orderBy('name')
            ->get();

        $stocks = app(ProductStockCalculator::class)->forProducts(
            $products->pluck('id')->all()
        );

        $rows = $products
            ->map(function (Product $product) use ($stocks) {
                return [
                    'product' => $product,
                    'stock' => (float) ($stocks[$product->id] ?? 0),
                    'movement_count' => (int) ($product->inventory_movements_count ?? 0),
                ];
            })
            ->values();

        $navigationTrail = InventoryNavigationTrail::index($request);

        return view('inventory.index', compact(
            'rows',
            'navigationTrail',
        ));
    }

    public function show(Request $request, Product $product): View
    {
        $this->authorize('view', $product);

        abort_if(
            $product->kind !== ProductCatalog::KIND_PRODUCT,
            404
        );

        $product->load([
            'attachments' => fn ($query) => $query->ordered(),
        ]);

        $inventoryMovements = $product->inventoryMovements()
            ->with(['order', 'document', 'product'])
            ->latest('id')
            ->get();

        $currentStock = app(ProductStockCalculator::class)->forProduct($product);

        $navigationTrail = InventoryNavigationTrail::show($request, $product);

        return view('inventory.show', compact(
            'product',
            'inventoryMovements',
            'currentStock',
            'navigationTrail',
        ));
    }

    public function storeMovement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'kind' => ['required', 'string', Rule::in(InventoryMovementService::kinds())],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'order_id' => ['nullable', 'integer'],
            'document_id' => ['nullable', 'integer'],
            'return_context' => ['nullable', 'string'],
        ]);

        $product = $this->resolveProduct((int) $data['product_id']);

        abort_if(
            $product->kind !== ProductCatalog::KIND_PRODUCT,
            404
        );

        $order = $this->resolveOrderContext($data, $product);
        $document = $this->resolveDocumentContext($data, $product);

        if ($order) {
            $this->authorize('update', $order);
            $product = $this->resolveProductFromOrder($order, $product->id);
        } else {
            $this->authorize('update', $product);
        }

        $result = match ($data['kind']) {
            InventoryMovementService::KIND_INGRESAR => app(InventoryMovementService::class)->ingresar(
                product: $product,
                quantity: $data['quantity'],
                notes: $data['notes'] ?? null,
                order: $order,
                document: $document,
                createdBy: auth()->id(),
            ),
            InventoryMovementService::KIND_CONSUMIR => app(InventoryMovementService::class)->consumir(
                product: $product,
                quantity: $data['quantity'],
                notes: $data['notes'] ?? null,
                order: $order,
                document: $document,
                createdBy: auth()->id(),
            ),
            InventoryMovementService::KIND_ENTREGAR => app(InventoryMovementService::class)->entregar(
                product: $product,
                quantity: $data['quantity'],
                notes: $data['notes'] ?? null,
                order: $order,
                document: $document,
                createdBy: auth()->id(),
            ),
        };

        $redirect = $this->redirectAfterMovement(
            request: $request,
            product: $product,
            order: $order,
            returnContext: $data['return_context'] ?? null,
        )->with('success', 'Movimiento registrado correctamente.');

        if (($result['negative_stock'] ?? false) === true) {
            $redirect->with(
                'warning',
                'El producto quedó con stock negativo. Se generó una tarea automática para revisión del owner.'
            );
        }

        return $redirect;
    }

    protected function resolveProduct(int $productId): Product
    {
        return app(Security::class)
            ->scope(auth()->user(), 'products.viewAny', Product::query())
            ->where('kind', ProductCatalog::KIND_PRODUCT)
            ->whereKey($productId)
            ->firstOrFail();
    }

    protected function resolveOrderContext(array $data, Product $product): ?Order
    {
        if (empty($data['order_id'])) {
            return null;
        }

        return Order::query()
            ->where('tenant_id', $product->tenant_id)
            ->whereKey((int) $data['order_id'])
            ->firstOrFail();
    }

    protected function resolveDocumentContext(array $data, Product $product): ?Document
    {
        if (empty($data['document_id'])) {
            return null;
        }

        $document = Document::query()
            ->where('tenant_id', $product->tenant_id)
            ->whereKey((int) $data['document_id'])
            ->firstOrFail();

        $this->authorize('view', $document);

        return $document;
    }

    protected function resolveProductFromOrder(Order $order, int $productId): Product
    {
        $order->loadMissing('items.product');

        $product = $order->items
            ->filter(fn ($item) => $item->product && $item->product->kind === ProductCatalog::KIND_PRODUCT)
            ->map(fn ($item) => $item->product)
            ->first(fn ($product) => (int) $product->id === $productId);

        abort_if(! $product, 422, 'El producto seleccionado no pertenece a los ítems físicos de esta orden.');

        app(Security::class)
            ->scope(auth()->user(), 'products.viewAny', Product::query())
            ->where('tenant_id', $order->tenant_id)
            ->whereNull('deleted_at')
            ->whereKey($product->id)
            ->firstOrFail();

        return $product;
    }

    protected function redirectAfterMovement(
        Request $request,
        Product $product,
        ?Order $order = null,
        ?string $returnContext = null,
    ): RedirectResponse {
        $trail = NavigationTrail::decode($request->query('trail'));
        $trailQuery = NavigationTrail::toQuery($trail);

        return match ($returnContext) {
            'orders.show' => redirect()->route(
                'orders.show',
                ['order' => $order] + $trailQuery
            ),
            'products.show' => redirect()->route(
                'products.show',
                ['product' => $product] + $trailQuery
            ),
            default => redirect()->route(
                'inventory.show',
                ['product' => $product] + $trailQuery
            ),
        };
    }
}
