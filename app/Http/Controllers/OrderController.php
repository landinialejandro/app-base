<?php

// FILE: app/Http/Controllers/OrderController.php | V29

namespace App\Http\Controllers;

use App\Events\OperationalRecordCreated;
use App\Events\OperationalRecordUpdated;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Support\Assets\AssetOrderSelector;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Modules\ModuleSurfaceRegistry;
use App\Support\Modules\SurfaceHostContextBuilder;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
use App\Support\Numbering\RecordNumberGenerator;
use App\Support\Orders\OrdersHooks;
use App\Support\Orders\OrderSurfaceService;
use App\Support\Parties\PartyOrderSelector;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeServiceUniverse($request);
        $this->authorizeProductionUniverse($request);
        $this->authorize('viewAny', Order::class);

        $security = app(Security::class);
        $user = auth()->user();

        $q = trim((string) $request->get('q', ''));
        $group = $this->orderGroupFromRequest($request);
        $status = $request->get('status');
        $orderedAt = $request->get('ordered_at');

        $orders = $security
            ->scope($user, 'orders.viewAny', Order::query())
            ->with(['items', 'party', 'asset.party'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('number', 'like', "%{$q}%")
                        ->orWhere('counterparty_reference', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($group, fn ($query) => $query->where('group', $group))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($orderedAt, fn ($query) => $query->whereDate('ordered_at', $orderedAt))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $allowedCreateGroups = collect(array_keys(OrderCatalog::groups()))
            ->filter(fn (string $orderGroup) => $security->allows(
                $user,
                'orders.create',
                Order::class,
                ['kind' => $orderGroup]
            ))
            ->values();

        $canCreateOrders = $allowedCreateGroups->isNotEmpty();
        $defaultCreateKind = $this->isServiceUniverse($request)
            ? OrderCatalog::GROUP_SERVICE
            : $allowedCreateGroups->first();

        $isServiceUniverse = $this->isServiceUniverse($request);

        return view('orders.index', compact(
            'orders',
            'canCreateOrders',
            'defaultCreateKind',
            'isServiceUniverse',
        ));
    }

public function create(Request $request)
{
    $this->authorizeServiceUniverse($request);
    $this->authorizeProductionUniverse($request);

    $security = app(Security::class);
    $user = auth()->user();

    $prefilledGroup = old('group', OrderCatalog::GROUP_SALE);
    $prefilledKind = old('kind', OrderCatalog::KIND_STANDARD);

    $requestedGroup = $this->orderGroupFromRequest($request);
    $requestedKind = (string) $request->get('kind', '');

    if ($requestedGroup !== null) {
        $prefilledGroup = $requestedGroup;
    }

    if ($requestedKind !== '' && in_array($requestedKind, array_keys(OrderCatalog::kindLabels()), true)) {
        $prefilledKind = $requestedKind;
    }

    $security->authorize(
        $user,
        'orders.create',
        Order::class,
        ['kind' => $prefilledGroup]
    );

    $navigationTrail = OrderNavigationTrail::create($request);

    $relationshipBoundary = $this->relationshipBoundaryForOrderForm(
        request: $request,
        order: null,
        mode: 'create',
        fieldDefaults: [
            'party_id' => '',
            'asset_id' => '',
            'counterparty_reference' => '',
            'group' => $prefilledGroup,
            'kind' => $prefilledKind,
        ],
    );

    $groupLocked = $this->isServiceUniverse($request) || $this->isProductionUniverse($request);

    return view('orders.create', compact(
        'prefilledGroup',
        'prefilledKind',
        'navigationTrail',
        'relationshipBoundary',
        'groupLocked',
    ));
}

public function store(Request $request)
{
    $this->authorizeServiceUniverse($request);
        $this->authorizeProductionUniverse($request);

    if ($this->isServiceUniverse($request)) {
        $request->merge([
            'group' => OrderCatalog::GROUP_SERVICE,
            'kind' => $request->input('kind', OrderCatalog::KIND_STANDARD),
        ]);
    }

    $tenant = app('tenant');
    $security = app(Security::class);
    $user = auth()->user();

    $data = $request->validate([
        'party_id' => ['nullable', 'integer'],
        'counterparty_reference' => ['nullable', 'string', 'max:255'],
        'asset_id' => ['nullable', 'integer'],
        'asset_reference' => ['nullable', 'string', 'max:255'],
        'group' => [
            'required',
            Rule::in(array_keys(OrderCatalog::groups())),
        ],
        'kind' => [
            'required',
            Rule::in(array_keys(OrderCatalog::kindLabels())),
        ],
        'status' => [
            'required',
            Rule::in(OrderCatalog::statuses()),
        ],
        'ordered_at' => ['nullable', 'date'],
        'notes' => ['nullable', 'string'],
    ]);

    $security->authorize(
        $user,
        'orders.create',
        Order::class,
        ['kind' => $data['group']]
    );

    $data = $this->normalizeLinkedOrderData($data, $tenant->id, $user);

    if ($data['counterparty_reference'] === '') {
        return back()
            ->withErrors([
                'counterparty_reference' => 'Debes escribir una contraparte o seleccionar un contacto vinculado.',
            ])
            ->withInput();
    }

    $orderedAt = $data['ordered_at'] ?? now()->toDateString();
    $orderedAtDate = Carbon::parse($orderedAt)->startOfDay();
    $maxFutureDate = now()->startOfDay()->addDays(30);

    if ($orderedAtDate->gt($maxFutureDate)) {
        return back()
            ->withErrors([
                'ordered_at' => 'La fecha de la orden no puede superar los 30 días hacia el futuro.',
            ])
            ->withInput();
    }

    $data['ordered_at'] = $orderedAtDate->toDateString();

    $order = DB::transaction(function () use ($tenant, $data) {
        $sequenceDefinition = OrderCatalog::sequenceDefinitionForGroup($data['group']);

        $sequence = RecordNumberGenerator::generate(
            tenantId: $tenant->id,
            kind: $sequenceDefinition['kind'],
            defaultPrefix: $sequenceDefinition['prefix'],
            pointOfSale: '0001',
        );

        $payload = array_merge($data, [
            'number' => $sequence['number'],
            'sequence_prefix' => $sequence['prefix'],
            'point_of_sale' => $sequence['point_of_sale'],
            'sequence_number' => $sequence['sequence_number'],
            'created_by' => auth()->id(),
        ]);

        return Order::create($payload);
    });

    event(new OperationalRecordCreated(
        record: $order,
        actorUserId: auth()->id(),
    ));

    $navigationTrail = OrderNavigationTrail::show($request, $order);

    $showRouteName = $this->isServiceUniverse($request)
        ? 'service.orders.show'
        : 'orders.show';

    return redirect()
        ->route($showRouteName, ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
        ->with('success', "Orden creada correctamente con número {$order->number}.");
}

    public function show(Request $request, Order $order)
    {
        $this->authorizeServiceUniverse($request);
        $this->authorizeProductionUniverse($request);
        $this->abortUnlessServiceOrder($request, $order);
        $this->abortUnlessProductionOrder($request, $order);

        $this->authorize('view', $order);

        $tenant = app('tenant');

        $supportsProductsModule = TenantModuleAccess::isEnabled(ModuleCatalog::PRODUCTS, $tenant);
        $supportsDocumentsModule = TenantModuleAccess::isEnabled(ModuleCatalog::DOCUMENTS, $tenant);

        $order->load([
            'creator',
            'updater',
            'party',
            'asset.party',
            'items.product',
            'documents',
            'attachments' => fn ($query) => $query->ordered(),
        ]);

        $navigationTrail = OrderNavigationTrail::show($request, $order);

        return view('orders.show', compact(
            'order',
            'navigationTrail',
            'supportsProductsModule',
            'supportsDocumentsModule',
        ));
    }

    public function edit(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $user = auth()->user();

        $navigationTrail = OrderNavigationTrail::edit($request, $order);

        $relationshipBoundary = $this->relationshipBoundaryForOrderForm(
            request: $request,
            order: $order,
            mode: 'edit',
        );

        return view('orders.edit', compact(
            'order',
            'navigationTrail',
            'relationshipBoundary',
        ));
    }

public function update(Request $request, Order $order)
{
    $this->authorize('update', $order);

    abort_if(
        OrderCatalog::isReadonlyStatus($order->status),
        422,
        'No se puede editar una orden en estado readonly.'
    );

    $tenant = app('tenant');
    $security = app(Security::class);
    $user = auth()->user();

    $data = $request->validate([
        'party_id' => ['nullable', 'integer'],
        'counterparty_reference' => ['nullable', 'string', 'max:255'],
        'asset_id' => ['nullable', 'integer'],
        'asset_reference' => ['nullable', 'string', 'max:255'],
        'group' => [
            'required',
            Rule::in(array_keys(OrderCatalog::groups())),
        ],
        'kind' => [
            'required',
            Rule::in(array_keys(OrderCatalog::kindLabels())),
        ],
        'status' => [
            'required',
            Rule::in(OrderCatalog::statuses()),
        ],
        'ordered_at' => ['nullable', 'date'],
        'notes' => ['nullable', 'string'],
    ]);

    if ($order->number) {
        $data['group'] = $order->group;
        $data['kind'] = $order->kind;
    }

    $security->authorize(
        $user,
        'orders.update',
        $order,
        ['kind' => $order->group]
    );

    $data = $this->normalizeLinkedOrderData($data, $tenant->id, $user);

    if (! OrderCatalog::canTransition($order->status, $data['status'])) {
        return back()
            ->withErrors([
                'status' => 'La transición de estado solicitada no es válida.',
            ])
            ->withInput();
    }

    $hasExternalMovements = app(OrdersHooks::class)
        ->hasExternalMovements($order);

    if ($data['status'] === OrderCatalog::STATUS_CANCELLED && $hasExternalMovements) {
        return back()
            ->withErrors([
                'status' => 'No se puede cancelar una orden con movimientos registrados. Primero deben revertirse.',
            ])
            ->withInput();
    }

    if ($data['status'] === OrderCatalog::STATUS_CLOSED) {
        if (app(OrdersHooks::class)->hasCloseBlockers($order)) {
            return back()
                ->withErrors([
                    'status' => 'No se puede cerrar la orden mientras existan líneas pendientes o parciales.',
                ])
                ->withInput();
        }
    }

    if ($data['counterparty_reference'] === '') {
        return back()
            ->withErrors([
                'counterparty_reference' => 'Debes escribir una contraparte o seleccionar un contacto vinculado.',
            ])
            ->withInput();
    }

    $orderedAt = $data['ordered_at'] ?? $order->ordered_at?->toDateString() ?? now()->toDateString();
    $orderedAtDate = Carbon::parse($orderedAt)->startOfDay();
    $maxFutureDate = now()->startOfDay()->addDays(30);

    if ($orderedAtDate->gt($maxFutureDate)) {
        return back()
            ->withErrors([
                'ordered_at' => 'La fecha de la orden no puede superar los 30 días hacia el futuro.',
            ])
            ->withInput();
    }

    $data['ordered_at'] = $orderedAtDate->toDateString();
    $data['updated_by'] = auth()->id();

    $beforeAttributes = $order->getAttributes();

    $order->update($data);

    event(new OperationalRecordUpdated(
        record: $order,
        beforeAttributes: $beforeAttributes,
        actorUserId: auth()->id(),
    ));

    $navigationTrail = OrderNavigationTrail::show($request, $order);

    return redirect()
        ->route(
            OrderNavigationTrail::showRouteName($request, $navigationTrail),
            ['order' => $order] + NavigationTrail::toQuery($navigationTrail)
        )
        ->with('success', 'Orden actualizada.');
}

public function updateStatus(Request $request, Order $order)
{
    $this->authorize('changeStatus', $order);

    $data = $request->validate([
        'status' => ['required', Rule::in(OrderCatalog::statuses())],
    ]);

    $newStatus = $data['status'];

    if (! OrderCatalog::canTransition($order->status, $newStatus)) {
        return back()
            ->withErrors([
                'status' => 'La transición de estado solicitada no es válida.',
            ]);
    }

    $hasExternalMovements = app(OrdersHooks::class)
        ->hasExternalMovements($order);

    if ($newStatus === OrderCatalog::STATUS_CANCELLED && $hasExternalMovements) {
        return back()
            ->withErrors([
                'status' => 'No se puede cancelar una orden con movimientos registrados. Primero deben revertirse.',
            ]);
    }

    if ($newStatus === OrderCatalog::STATUS_CLOSED) {
        if (app(OrdersHooks::class)->hasCloseBlockers($order)) {
            return back()
                ->withErrors([
                    'status' => 'No se puede cerrar la orden mientras existan líneas pendientes o parciales.',
                ]);
        }
    }

    $beforeAttributes = $order->getAttributes();

    $order->update([
        'status' => $newStatus,
        'updated_by' => auth()->id(),
    ]);

    event(new OperationalRecordUpdated(
        record: $order,
        beforeAttributes: $beforeAttributes,
        actorUserId: auth()->id(),
    ));

    $navigationTrail = OrderNavigationTrail::show($request, $order);

    return redirect()
        ->route(
            OrderNavigationTrail::showRouteName($request, $navigationTrail),
            ['order' => $order] + NavigationTrail::toQuery($navigationTrail)
        )
        ->with('success', 'Estado de la orden actualizado.');
}

public function destroy(Request $request, Order $order)
{
    $this->authorize('delete', $order);

    if ($order->documents()->exists()) {
        return back()->withErrors([
            'delete' => 'No se puede eliminar una orden vinculada a documentos.',
        ]);
    }

    if (app(OrdersHooks::class)->hasExternalMovements($order)) {
        return back()->withErrors([
            'delete' => 'No se puede eliminar una orden con movimientos registrados.',
        ]);
    }

    $order->delete();

    $navigationTrail = OrderNavigationTrail::index($request);

    return redirect()
        ->route(
            OrderNavigationTrail::indexRouteName($request, $navigationTrail),
            NavigationTrail::toQuery($navigationTrail)
        )
        ->with('success', 'Orden eliminada.');
}

    public function print(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'party',
            'asset.party',
            'items.product',
        ]);

        return view('orders.print', [
            'order' => $order,
            'renderMode' => 'print',
        ]);
    }

    public function pdf(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'party',
            'asset.party',
            'items.product',
        ]);

        $filename = $order->number
            ? 'orden-'.strtolower(str_replace([' ', '/'], '-', $order->number)).'.pdf'
            : 'orden-'.$order->id.'.pdf';

        $pdf = Pdf::loadView('orders.print', [
            'order' => $order,
            'renderMode' => 'pdf',
        ]);

        return $pdf->download($filename);
    }

    public function changeStatus(Request $request, Order $order)
    {
        $this->authorize('changeStatus', $order);

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(OrderCatalog::statuses()),
            ],
        ]);

        $newStatus = $data['status'];

        if (! OrderCatalog::canTransition($order->status, $newStatus)) {
            return back()
                ->withErrors([
                    'status' => 'La transición de estado solicitada no es válida.',
                ]);
        }

        if (
            $newStatus === OrderCatalog::STATUS_CANCELLED
            && app(OrdersHooks::class)->hasExternalMovements($order)
        ) {
            return back()
                ->withErrors([
                    'status' => 'No se puede cancelar una orden con movimientos registrados. Primero deben revertirse.',
                ]);
        }

        if ($newStatus === OrderCatalog::STATUS_CLOSED) {
            if (app(OrdersHooks::class)->hasCloseBlockers($order)) {
                return back()
                    ->withErrors([
                        'status' => 'No se puede cerrar la orden mientras existan líneas pendientes o parciales.',
                    ]);
            }
        }

        $beforeAttributes = $order->getAttributes();

        $order->update([
            'status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        event(new OperationalRecordUpdated(
            record: $order,
            beforeAttributes: $beforeAttributes,
            actorUserId: auth()->id(),
        ));

        return back()->with('success', 'Estado de la orden actualizado.');
    }

    protected function partyOptionsFor($user)
    {
        $tenant = app('tenant');

        if (! TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant)) {
            return collect();
        }

        if (! app(Security::class)->allows($user, ModuleCatalog::PARTIES.'.viewAny')) {
            return collect();
        }

        return app(PartyOrderSelector::class)->optionsFor($user);
    }

    protected function assetOptionsFor($user)
    {
        $tenant = app('tenant');

        if (! TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant)) {
            return collect();
        }

        if (! app(Security::class)->allows($user, ModuleCatalog::ASSETS.'.viewAny')) {
            return collect();
        }

        return app(AssetOrderSelector::class)->optionsFor($user);
    }

    protected function normalizeLinkedOrderData(array $data, string $tenantId, $user): array
    {
        $data['party_id'] = $this->normalizeNullableId($data['party_id'] ?? null);
        $data['asset_id'] = $this->normalizeNullableId($data['asset_id'] ?? null);

        $data['counterparty_reference'] = trim((string) ($data['counterparty_reference'] ?? ''));
        $data['asset_reference'] = trim((string) ($data['asset_reference'] ?? ''));

        $party = null;
        $asset = null;

        if ($data['party_id'] !== null) {
            $party = app(Security::class)
                ->scope($user, ModuleCatalog::PARTIES.'.viewAny', Party::query())
                ->where('tenant_id', $tenantId)
                ->whereKey($data['party_id'])
                ->first();

            if (! $party) {
                abort(422, 'El contacto seleccionado no está disponible para esta orden.');
            }
        }

        if ($data['asset_id'] !== null) {
            $asset = app(Security::class)
                ->scope($user, ModuleCatalog::ASSETS.'.viewAny', Asset::query())
                ->where('tenant_id', $tenantId)
                ->whereKey($data['asset_id'])
                ->first();

            if (! $asset) {
                abort(422, 'El activo seleccionado no está disponible para esta orden.');
            }
        }

        if (
            $party
            && $asset
            && $asset->party_id !== null
            && (int) $asset->party_id !== (int) $party->id
        ) {
            abort(422, 'El activo seleccionado no pertenece al contacto vinculado.');
        }

        if ($data['counterparty_reference'] === '' && $party) {
            $data['counterparty_reference'] = trim((string) ($party->display_name ?: $party->name));
        }

        if ($data['asset_reference'] === '' && $asset) {
            $data['asset_reference'] = trim((string) ($asset->internal_code ?: $asset->name));
        }

        $data['record_metadata'] = $this->relationshipMetadataForOrderData($data);

        return $data;
    }

    protected function normalizeNullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function relationshipFieldsForOrderForm(Request $request, ?Order $order, string $mode, array $fieldDefaults = []): array
    {
        $hostPack = app(OrderSurfaceService::class)->hostPack(
            'orders.form',
            $order,
            [
                'mode' => $mode,
                'modelClass' => Order::class,
                'fieldNames' => [
                    'party_id',
                    'asset_id',
                    'counterparty_reference',
                ],
                'fieldDefaults' => $fieldDefaults,
                'trailQuery' => [],
            ],
        );

        $formHostPack = app(SurfaceHostContextBuilder::class)
            ->forForm($hostPack);

        return app(ModuleSurfaceRegistry::class)
            ->slotFor('orders.form', 'relationship_fields', $formHostPack);
    }

    protected function relationshipBoundaryForOrderForm(Request $request, ?Order $order, string $mode, array $fieldDefaults = []): array
    {
        $fields = $this->relationshipFieldsForOrderForm(
            request: $request,
            order: $order,
            mode: $mode,
            fieldDefaults: $fieldDefaults,
        );

        $visibleFields = collect($fields)
            ->filter(fn (array $surface) => ($surface['visible'] ?? true) && ! empty($surface['view']))
            ->values();

        $fieldsByKey = $visibleFields->keyBy(fn (array $surface) => $surface['key'] ?? '');

        $partySurface = $fieldsByKey->get('party.order.form-context');
        $assetSurface = $fieldsByKey->get('asset.order.form-context');

        $partyMode = $partySurface && ($mode === 'create' || ($order instanceof Order && $order->party_id !== null))
            ? 'external'
            : 'manual';

        $assetMode = $assetSurface && ($mode === 'create' || ($order instanceof Order && $order->asset_id !== null))
            ? 'external'
            : 'manual';

        return [
            'party' => [
                'mode' => $partyMode,
                'surface' => $partyMode === 'external' ? $partySurface : null,
            ],
            'asset' => [
                'mode' => $assetMode,
                'surface' => $assetMode === 'external' ? $assetSurface : null,
            ],
            'available' => [
                'party' => (bool) $partySurface,
                'asset' => (bool) $assetSurface,
            ],
        ];
    }

    protected function relationshipMetadataForOrderData(array $data): ?array
    {
        $relationships = [];

        if (($data['party_id'] ?? null) === null && trim((string) ($data['counterparty_reference'] ?? '')) !== '') {
            $relationships['counterparty'] = [
                'managed' => false,
            ];
        }

        if (($data['asset_id'] ?? null) === null && trim((string) ($data['asset_reference'] ?? '')) !== '') {
            $relationships['asset'] = [
                'managed' => false,
            ];
        }

        if (empty($relationships)) {
            return null;
        }

        return [
            'relationships' => $relationships,
        ];
    }

private function isServiceUniverse(Request $request): bool
{
    return $request->routeIs('service.orders.*');
}

private function authorizeServiceUniverse(Request $request): void
{
    if (! $this->isServiceUniverse($request)) {
        return;
    }

    $tenant = app('tenant');
    $user = auth()->user();
    $security = app(Security::class);

    abort_unless(
        TenantModuleAccess::isEnabled(ModuleCatalog::SERVICE_MAINTENANCE, $tenant)
            && $security->allows($user, ModuleCatalog::SERVICE_MAINTENANCE.'.viewAny'),
        403
    );
}

private function orderGroupFromRequest(Request $request): ?string
{
    if ($this->isServiceUniverse($request)) {
        return OrderCatalog::GROUP_SERVICE;
    }

    if ($this->isProductionUniverse($request)) {
        return OrderCatalog::GROUP_PRODUCTION;
    }

    $group = (string) $request->get('group', '');

    return $group !== '' && array_key_exists($group, OrderCatalog::groups())
        ? $group
        : null;
}

    private function abortUnlessServiceOrder(Request $request, Order $order): void
    {
        if (! $this->isServiceUniverse($request)) {
            return;
        }

        abort_unless($order->group === OrderCatalog::GROUP_SERVICE, 404);
    }


    private function isProductionUniverse(Request $request): bool
    {
        return $request->routeIs('production.orders.*');
    }


    private function authorizeProductionUniverse(Request $request): void
    {
        if (! $this->isProductionUniverse($request)) {
            return;
        }
    
        $user = auth()->user();
        $security = app(Security::class);
    
        abort_unless(
            $security->allows($user, ModuleCatalog::ORDERS.'.viewAny'),
            403
        );
    }


    private function abortUnlessProductionOrder(Request $request, Order $order): void
    {
        if (! $this->isProductionUniverse($request)) {
            return;
        }
    
        abort_unless($order->group === OrderCatalog::GROUP_PRODUCTION, 404);
    }
}
