<?php

// FILE: app/Http/Controllers/InventoryController.php | V10

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Auth\Security;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\InventoryMovementService;
use App\Support\Inventory\InventorySurfaceService;
use App\Support\Inventory\OrderInventoryOperationService;
use App\Support\Inventory\ProductStockCalculator;
use App\Support\Modules\ModuleSurfaceRegistry;
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
        $navigationTrail = InventoryNavigationTrail::index($request);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $query = trim((string) $request->string('q'));

        $productsQuery = app(Security::class)
            ->scope(auth()->user(), 'products.viewAny', Product::query())
            ->where('kind', ProductCatalog::KIND_PRODUCT)
            ->with([
                'inventoryMovements:id,product_id,kind,quantity',
            ]);

        if ($query !== '') {
            $productsQuery->where(function ($builder) use ($query) {
                $builder
                    ->where('name', 'like', '%'.$query.'%')
                    ->orWhere('sku', 'like', '%'.$query.'%');

                if (ctype_digit($query)) {
                    $builder->orWhereKey((int) $query);
                }
            });
        }

        $products = $productsQuery
            ->orderBy('name')
            ->get();

        $stocks = app(ProductStockCalculator::class)->forProducts(
            $products->pluck('id')->all()
        );

        $rows = $products
            ->map(function (Product $product) use ($stocks) {
                $movements = $product->inventoryMovements ?? collect();

                $totalIn = (float) $movements
                    ->whereIn('kind', [
                        InventoryMovementService::KIND_INGRESAR,
                    ])
                    ->sum('quantity');

                $totalOut = (float) $movements
                    ->whereIn('kind', [
                        InventoryMovementService::KIND_CONSUMIR,
                        InventoryMovementService::KIND_ENTREGAR,
                    ])
                    ->sum('quantity');

                return [
                    'product' => $product,
                    'total_in' => $totalIn,
                    'total_out' => $totalOut,
                    'balance' => (float) ($stocks[$product->id] ?? 0),
                ];
            })
            ->values();

        $hostPack = app(InventorySurfaceService::class)->hostPack('inventory.index', null, [
            'trailQuery' => $trailQuery,
        ]);

        $linked = collect(
            app(ModuleSurfaceRegistry::class)->linkedFor('inventory.index', $hostPack)
        )->values();

        $headerActions = $linked
            ->where('slot', 'header_actions')
            ->values();

        return view('inventory.index', compact(
            'rows',
            'navigationTrail',
            'headerActions',
        ));
    }

    public function show(Request $request, Product $product): View
    {
        $this->authorize('view', $product);

        abort_if(
            $product->kind !== ProductCatalog::KIND_PRODUCT,
            404
        );

        $movementKind = trim((string) $request->string('kind'));

        if ($movementKind !== '' && ! in_array($movementKind, InventoryMovementService::kinds(), true)) {
            abort(404);
        }

        $navigationTrail = InventoryNavigationTrail::show($request, $product);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $product->load([
            'attachments' => fn ($query) => $query->ordered(),
        ]);

        $movementsQuery = $product->inventoryMovements()
            ->with(['order', 'orderItem', 'document', 'product'])
            ->orderBy('created_at')
            ->orderBy('id');

        if ($movementKind !== '') {
            $movementsQuery->where('kind', $movementKind);
        }

        $inventoryMovements = $movementsQuery->get();

        $runningBalance = 0.0;

        $movementRows = $inventoryMovements
            ->map(function ($movement) use (&$runningBalance) {
                $quantity = (float) $movement->quantity;
                $signedQuantity = $this->signedQuantityForMovement($movement->kind, $quantity);

                $runningBalance += $signedQuantity;

                return [
                    'movement' => $movement,
                    'signed_quantity' => $signedQuantity,
                    'running_balance' => $runningBalance,
                ];
            })
            ->values()
            ->reverse()
            ->values();

        $currentStock = app(ProductStockCalculator::class)->forProduct($product);

        $hostPack = app(InventorySurfaceService::class)->hostPack('inventory.show', $product, [
            'trailQuery' => $trailQuery,
        ]);

        $embedded = collect(app(ModuleSurfaceRegistry::class)->embeddedFor('inventory.show', $hostPack))->values();
        $linked = collect(app(ModuleSurfaceRegistry::class)->linkedFor('inventory.show', $hostPack))->values();

        $summaryItems = $linked->where('slot', 'summary_items')->values();
        $headerActions = $linked->where('slot', 'header_actions')->values();
        $detailItems = $embedded->where('slot', 'detail_items')->values();
        $tabItems = $embedded->where(fn ($item) => ($item['slot'] ?? 'tab_panels') === 'tab_panels')->values();

        return view('inventory.show', compact(
            'product',
            'movementRows',
            'currentStock',
            'navigationTrail',
            'movementKind',
            'summaryItems',
            'headerActions',
            'detailItems',
            'tabItems',
        ));
    }

    public function createMovement(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        abort_if(
            $product->kind !== ProductCatalog::KIND_PRODUCT,
            404
        );

        $navigationTrail = InventoryNavigationTrail::show($request, $product);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            route('inventory.show', ['product' => $product] + $trailQuery)
        );

        return view('inventory.create-movement', compact(
            'product',
            'navigationTrail',
            'breadcrumbItems',
            'trailQuery',
            'cancelUrl',
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
            'order_item_id' => ['nullable', 'integer'],
            'document_id' => ['nullable', 'integer'],
            'return_context' => ['nullable', 'string', Rule::in([
                'inventory.show',
                'orders.show',
                'products.show',
            ])],
        ]);

        $product = $this->resolveProduct((int) $data['product_id']);

        abort_if(
            $product->kind !== ProductCatalog::KIND_PRODUCT,
            404
        );

        $order = $this->resolveOrderContext($data, $product);
        $orderItem = $this->resolveOrderItemContext($data, $order, $product);
        $document = $this->resolveDocumentContext($data, $product);

        $this->validateMovementContext($data, $order, $orderItem);

        if ($order) {
            $this->authorize('update', $order);
            $product = $this->resolveProductFromOrder($order, $product->id);
            $this->validateOrderOperable($order);
        } else {
            $this->authorize('update', $product);
        }

        $result = $order
            ? $this->storeOrderLineMovement(
                order: $order,
                orderItem: $orderItem,
                quantity: $data['quantity'],
                notes: $data['notes'] ?? null,
                createdBy: auth()->id(),
            )
            : $this->storeManualMovement(
                product: $product,
                kind: $data['kind'],
                quantity: $data['quantity'],
                notes: $data['notes'] ?? null,
                document: $document,
                createdBy: auth()->id(),
            );

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

    protected function storeOrderLineMovement(
        Order $order,
        OrderItem $orderItem,
        float|int|string $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): array {
        return app(OrderInventoryOperationService::class)->executeLine(
            order: $order,
            item: $orderItem,
            quantity: $quantity,
            notes: $notes,
            createdBy: $createdBy,
        );
    }

    protected function storeManualMovement(
        Product $product,
        string $kind,
        float|int|string $quantity,
        ?string $notes = null,
        ?Document $document = null,
        int|string|null $createdBy = null,
    ): array {
        return match ($kind) {
            InventoryMovementService::KIND_INGRESAR => app(InventoryMovementService::class)->ingresar(
                product: $product,
                quantity: $quantity,
                notes: $notes,
                order: null,
                document: $document,
                createdBy: $createdBy,
            ),
            InventoryMovementService::KIND_CONSUMIR => app(InventoryMovementService::class)->consumir(
                product: $product,
                quantity: $quantity,
                notes: $notes,
                order: null,
                document: $document,
                createdBy: $createdBy,
            ),
            InventoryMovementService::KIND_ENTREGAR => app(InventoryMovementService::class)->entregar(
                product: $product,
                quantity: $quantity,
                notes: $notes,
                order: null,
                document: $document,
                createdBy: $createdBy,
            ),
        };
    }

    protected function validateMovementContext(
        array $data,
        ?Order $order,
        ?OrderItem $orderItem,
    ): void {
        $hasOrderId = ! empty($data['order_id']);
        $hasOrderItemId = ! empty($data['order_item_id']);

        if ($hasOrderId xor $hasOrderItemId) {
            abort(422, 'Los movimientos operativos de orden requieren orden y línea asociadas.');
        }

        if (! $hasOrderId && ! $hasOrderItemId) {
            return;
        }

        abort_if(! $order, 422, 'La orden indicada no es válida.');
        abort_if(! $orderItem, 422, 'La línea indicada no es válida.');
    }

    protected function validateOrderOperable(Order $order): void
    {
        abort_if(
            $order->status !== OrderCatalog::STATUS_APPROVED,
            422,
            'La orden no está en estado operable para inventory.'
        );
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

    protected function resolveOrderItemContext(array $data, ?Order $order, Product $product): ?OrderItem
    {
        if (empty($data['order_item_id'])) {
            return null;
        }

        abort_if(! $order, 422, 'La línea de orden requiere una orden asociada.');

        $item = OrderItem::query()
            ->where('tenant_id', $product->tenant_id)
            ->where('order_id', $order->id)
            ->whereKey((int) $data['order_item_id'])
            ->firstOrFail();

        abort_if(
            (int) $item->product_id !== (int) $product->id,
            422,
            'La línea indicada no corresponde al producto seleccionado.'
        );

        return $item;
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
            'inventory.show', null => redirect()->route(
                'inventory.show',
                ['product' => $product] + $trailQuery
            ),
            default => redirect()->route(
                'inventory.show',
                ['product' => $product] + $trailQuery
            ),
        };
    }

    protected function signedQuantityForMovement(string $kind, float $quantity): float
    {
        return match ($kind) {
            InventoryMovementService::KIND_INGRESAR => $quantity,
            InventoryMovementService::KIND_CONSUMIR,
            InventoryMovementService::KIND_ENTREGAR => -1 * $quantity,
            default => 0.0,
        };
    }
}
