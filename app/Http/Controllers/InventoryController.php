<?php

// FILE: app/Http/Controllers/InventoryController.php | V13

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Auth\Security;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\InventoryMovementService;
use App\Support\Inventory\InventoryOriginCatalog;
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
            ->where('kind', ProductCatalog::KIND_PRODUCT);

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

        $movementTotals = InventoryMovement::query()
            ->whereIn('product_id', $products->pluck('id')->all())
            ->selectRaw('
            product_id,
            COALESCE(SUM(CASE WHEN kind = ? THEN quantity ELSE 0 END), 0) as total_in,
            COALESCE(SUM(CASE WHEN kind IN (?, ?) THEN quantity ELSE 0 END), 0) as total_out
        ', [
                InventoryMovementService::KIND_INGRESAR,
                InventoryMovementService::KIND_CONSUMIR,
                InventoryMovementService::KIND_ENTREGAR,
            ])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $rows = $products
            ->map(function (Product $product) use ($stocks, $movementTotals) {
                $totals = $movementTotals->get($product->id);

                return [
                    'product' => $product,
                    'total_in' => (float) ($totals->total_in ?? 0),
                    'total_out' => (float) ($totals->total_out ?? 0),
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
    $originLineType = trim((string) $request->string('origin_line_type'));
    $originLineId = $request->integer('origin_line_id');

    if ($movementKind !== '' && ! in_array($movementKind, InventoryMovementService::kinds(), true)) {
        abort(404);
    }

    if (
        $originLineType !== ''
        && ! in_array($originLineType, InventoryOriginCatalog::originLineTypes(), true)
    ) {
        abort(404);
    }

    if ($originLineType === '' && $originLineId > 0) {
        abort(404);
    }

    if ($originLineType !== '' && $originLineId <= 0) {
        abort(404);
    }

    $navigationTrail = InventoryNavigationTrail::show($request, $product);
    $trailQuery = NavigationTrail::toQuery($navigationTrail);

    $product->load([
        'attachments' => fn ($query) => $query->ordered(),
    ]);

    $movementsQuery = InventoryMovement::query()
        ->where('tenant_id', $product->tenant_id)
        ->where('product_id', $product->id)
        ->with([
            'product',
            'operation',
        ])
        ->orderBy('created_at')
        ->orderBy('id');

    if ($movementKind !== '') {
        $movementsQuery->where('kind', $movementKind);
    }

    if ($originLineType !== '') {
        $movementsQuery
            ->where('origin_line_type', $originLineType)
            ->where('origin_line_id', $originLineId);
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
        'originLineType',
        'originLineId',
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
        'origin_type' => ['nullable', 'string', Rule::in(InventoryOriginCatalog::originTypes())],
        'origin_id' => ['nullable', 'integer'],
        'origin_line_type' => ['nullable', 'string', Rule::in(InventoryOriginCatalog::originLineTypes())],
        'origin_line_id' => ['nullable', 'integer'],
        'return_context' => ['nullable', 'string', Rule::in([
            'inventory.show',
            'orders.show',
            'products.show',
            'documents.show',
        ])],
        'return_tab' => ['nullable', 'string'],
    ]);

    $product = $this->resolveProduct((int) $data['product_id']);

    abort_if(
        $product->kind !== ProductCatalog::KIND_PRODUCT,
        404
    );

    $order = $this->resolveOrderContext($data, $product);
    $orderItem = $this->resolveOrderItemContext($data, $order, $product);
    $document = $this->resolveDocumentContext($data, $product);

    $this->validateMovementContext($data, $order, $orderItem, $document);

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
            kind: $data['kind'],
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
        document: $document,
        returnContext: $data['return_context'] ?? null,
        returnTab: $data['return_tab'] ?? null,
    )->with('success', 'Movimiento registrado correctamente.');

    if (($result['negative_stock'] ?? false) === true) {
        $redirect->with(
            'warning',
            'El producto quedó con stock negativo. Se generó una tarea automática para revisión del owner.'
        );
    }

    return $redirect;
}

    public function returnOrderItemQuantity(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        abort_unless((int) $item->order_id === (int) $order->id, 404);

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'return_context' => ['nullable', 'string', Rule::in([
                'orders.show',
            ])],
            'return_tab' => ['nullable', 'string'],
        ]);

        $this->authorize('update', $order);
        $this->validateOrderOperable($order);

        $item->loadMissing(['product']);

        abort_if(
            ! $item->product || $item->product->kind !== ProductCatalog::KIND_PRODUCT,
            422,
            'La línea no corresponde a un producto físico stockeable.'
        );

        $result = app(OrderInventoryOperationService::class)->returnLineQuantity(
            order: $order,
            item: $item,
            quantity: $data['quantity'],
            notes: $data['notes'] ?? null,
            createdBy: auth()->id(),
        );

        $redirectQuery = NavigationTrail::toQuery(
            NavigationTrail::decode($request->query('trail'))
        );

        if (! empty($data['return_tab'])) {
            $redirectQuery['return_tab'] = $data['return_tab'];
        }

        $redirect = redirect()
            ->route('orders.show', ['order' => $order] + $redirectQuery)
            ->with('success', 'Devolución registrada correctamente.');

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
    string $kind,
    float|int|string $quantity,
    ?string $notes = null,
    int|string|null $createdBy = null,
): array {
    $profile = app(\App\Support\Inventory\InventoryOperationProfileResolver::class)
        ->forOrder($order);

    if ($kind === $profile['execute_kind']) {
        return app(OrderInventoryOperationService::class)->executeLine(
            order: $order,
            item: $orderItem,
            quantity: $quantity,
            notes: $notes,
            createdBy: $createdBy,
        );
    }

    if ($kind === $profile['reverse_kind']) {
        return app(OrderInventoryOperationService::class)->returnLineQuantity(
            order: $order,
            item: $orderItem,
            quantity: $quantity,
            notes: $notes,
            createdBy: $createdBy,
        );
    }

    abort(422, 'El tipo de movimiento no corresponde al perfil operativo de la orden.');
}

protected function storeManualMovement(
    Product $product,
    string $kind,
    float|int|string $quantity,
    ?string $notes = null,
    ?Document $document = null,
    int|string|null $createdBy = null,
): array {
    $operationType = $document
        ? \App\Support\Inventory\InventoryOperationCatalog::TYPE_DOCUMENT_MOVEMENT
        : \App\Support\Inventory\InventoryOperationCatalog::TYPE_MANUAL_ADJUSTMENT;

    $originType = $document
        ? \App\Support\Inventory\InventoryOriginCatalog::TYPE_DOCUMENT
        : \App\Support\Inventory\InventoryOriginCatalog::TYPE_MANUAL;

    $originId = $document?->id;

    return app(\App\Support\Inventory\InventoryOperationService::class)->run(
        tenantId: $product->tenant_id,
        operationType: $operationType,
        originType: $originType,
        originId: $originId,
        originLineType: null,
        originLineId: null,
        notes: $notes,
        createdBy: $createdBy,
        callback: function ($operation) use ($product, $kind, $quantity, $notes, $document, $createdBy) {
            return match ($kind) {
                InventoryMovementService::KIND_INGRESAR => app(InventoryMovementService::class)->ingresar(
                    product: $product,
                    quantity: $quantity,
                    notes: $notes,
                    order: null,
                    document: $document,
                    createdBy: $createdBy,
                    operation: $operation,
                ),
                InventoryMovementService::KIND_CONSUMIR => app(InventoryMovementService::class)->consumir(
                    product: $product,
                    quantity: $quantity,
                    notes: $notes,
                    order: null,
                    document: $document,
                    createdBy: $createdBy,
                    operation: $operation,
                ),
                InventoryMovementService::KIND_ENTREGAR => app(InventoryMovementService::class)->entregar(
                    product: $product,
                    quantity: $quantity,
                    notes: $notes,
                    order: null,
                    document: $document,
                    createdBy: $createdBy,
                    operation: $operation,
                ),
            };
        },
    );
}

    protected function validateMovementContext(
        array $data,
        ?Order $order,
        ?OrderItem $orderItem,
        ?Document $document = null,
    ): void {
        $originType = $data['origin_type'] ?? null;
        $originId = $data['origin_id'] ?? null;
        $originLineType = $data['origin_line_type'] ?? null;
        $originLineId = $data['origin_line_id'] ?? null;

        if (($originType === null || $originType === '') && ! empty($originId)) {
            abort(422, 'El origen del movimiento requiere tipo e ID.');
        }

        if (($originType !== null && $originType !== '') && empty($originId)) {
            abort(422, 'El origen del movimiento requiere tipo e ID.');
        }

        if (($originLineType === null || $originLineType === '') && ! empty($originLineId)) {
            abort(422, 'La línea de origen requiere tipo e ID.');
        }

        if (($originLineType !== null && $originLineType !== '') && empty($originLineId)) {
            abort(422, 'La línea de origen requiere tipo e ID.');
        }

        if ($originType === InventoryOriginCatalog::TYPE_ORDER) {
            abort_if(! $order, 422, 'La orden indicada no es válida.');
            abort_if(! $orderItem, 422, 'La línea indicada no es válida.');

            if ($originLineType !== InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM) {
                abort(422, 'Los movimientos de orden requieren línea de orden como origen.');
            }

            return;
        }

        if ($originType === InventoryOriginCatalog::TYPE_DOCUMENT) {
            abort_if(! $document, 422, 'El documento indicado no es válido.');

            if ($originLineType && $originLineType !== InventoryOriginCatalog::LINE_TYPE_DOCUMENT_ITEM) {
                abort(422, 'La línea de documento debe usar el tipo de línea documental.');
            }

            return;
        }

        if ($originType === InventoryOriginCatalog::TYPE_MANUAL || empty($originType)) {
            if ($originLineType || $originLineId) {
                abort(422, 'Un movimiento manual no puede tener línea de origen.');
            }

            return;
        }

        abort(422, 'Origen de movimiento inválido.');
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
        $originType = $data['origin_type'] ?? null;
        $originId = (int) ($data['origin_id'] ?? 0);

        if (
            $originType !== InventoryOriginCatalog::TYPE_ORDER
            || $originId <= 0
        ) {
            return null;
        }

        return Order::query()
            ->where('tenant_id', $product->tenant_id)
            ->findOrFail($originId);
    }

    protected function resolveOrderItemContext(array $data, ?Order $order, Product $product): ?OrderItem
    {
        $originLineType = $data['origin_line_type'] ?? null;
        $originLineId = (int) ($data['origin_line_id'] ?? 0);

        if (
            $originLineType !== InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM
            || $originLineId <= 0
        ) {
            return null;
        }

        abort_if(! $order, 422, 'La línea de orden requiere una orden asociada.');

        $item = OrderItem::query()
            ->where('tenant_id', $product->tenant_id)
            ->where('order_id', $order->id)
            ->whereKey($originLineId)
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
        $originType = $data['origin_type'] ?? null;
        $originId = (int) ($data['origin_id'] ?? 0);

        if (
            $originType !== InventoryOriginCatalog::TYPE_DOCUMENT
            || $originId <= 0
        ) {
            return null;
        }

        return Document::query()
            ->where('tenant_id', $product->tenant_id)
            ->findOrFail($originId);
    }

    protected function resolveProductFromOrder(Order $order, int $productId): Product
    {
        $order->loadMissing('items.product');

        $product = $order->items
            ->filter(fn ($item) => $item->product && $item->product->kind === ProductCatalog::KIND_PRODUCT)
            ->map(fn ($item) => $item->product)
            ->first(fn ($product) => (int) $product->id === (int) $productId);

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
        ?Document $document = null,
        ?string $returnContext = null,
        ?string $returnTab = null,
    ): RedirectResponse {
        $trail = NavigationTrail::decode($request->query('trail'));
        $trailQuery = NavigationTrail::toQuery($trail);

        if (! empty($returnTab)) {
            $trailQuery['return_tab'] = $returnTab;
        }

        return match ($returnContext) {
            'orders.show' => redirect()->route(
                'orders.show',
                ['order' => $order] + $trailQuery
            ),
            'documents.show' => redirect()->route(
                'documents.show',
                ['document' => $document] + $trailQuery
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

public function showMovement(Request $request, InventoryMovement $movement): View
{
    $movement->load([
        'product',
        'creator',
        'operation.creator',
    ]);

    abort_if(! $movement->product, 404);

    $this->authorize('view', $movement->product);

    $originOrder = null;
    $originDocument = null;
    $originOrderItem = null;

    if ($movement->origin_type === InventoryOriginCatalog::TYPE_ORDER && $movement->origin_id) {
        $originOrder = Order::query()
            ->where('tenant_id', $movement->tenant_id)
            ->whereKey($movement->origin_id)
            ->first();
    }

    if ($movement->origin_type === InventoryOriginCatalog::TYPE_DOCUMENT && $movement->origin_id) {
        $originDocument = Document::query()
            ->where('tenant_id', $movement->tenant_id)
            ->whereKey($movement->origin_id)
            ->first();
    }

    if ($movement->origin_line_type === InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM && $movement->origin_line_id) {
        $originOrderItem = OrderItem::query()
            ->where('tenant_id', $movement->tenant_id)
            ->whereKey($movement->origin_line_id)
            ->first();
    }

    $navigationTrail = InventoryNavigationTrail::movementShow($request, $movement);
    $trailQuery = NavigationTrail::toQuery($navigationTrail);

    $backUrl = NavigationTrail::previousUrl(
        $navigationTrail,
        route('inventory.show', ['product' => $movement->product] + $trailQuery)
    );

    return view('inventory.movement-show', compact(
        'movement',
        'originOrder',
        'originDocument',
        'originOrderItem',
        'navigationTrail',
        'trailQuery',
        'backUrl',
    ));
}

}
