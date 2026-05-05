<?php

// FILE: app/Http/Controllers/OrderController.php | V28

namespace App\Http\Controllers;

use App\Events\OperationalRecordCreated;
use App\Events\OperationalRecordUpdated;
use App\Models\Order;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
use App\Support\Orders\OrdersHooks;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $security = app(Security::class);
        $user = auth()->user();

        $q = trim((string) $request->get('q', ''));
        $group = $request->get('group');
        $status = $request->get('status');
        $orderedAt = $request->get('ordered_at');

        $orders = $security
            ->scope($user, 'orders.viewAny', Order::query())
            ->with(['items'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('number', 'like', "%{$q}%")
                        ->orWhere('counterparty_name', 'like', "%{$q}%");

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
        $defaultCreateKind = $allowedCreateGroups->first();

        return view('orders.index', compact(
            'orders',
            'canCreateOrders',
            'defaultCreateKind',
        ));
    }

    public function create(Request $request)
    {
        $security = app(Security::class);
        $user = auth()->user();

        $prefilledGroup = old('group', OrderCatalog::GROUP_SALE);
        $prefilledKind = old('kind', OrderCatalog::KIND_STANDARD);

        $requestedGroup = (string) $request->get('group', '');
        $requestedKind = (string) $request->get('kind', '');

        if ($requestedGroup !== '' && array_key_exists($requestedGroup, OrderCatalog::groups())) {
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

        return view('orders.create', compact(
            'prefilledGroup',
            'prefilledKind',
            'navigationTrail',
        ));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');
        $security = app(Security::class);
        $user = auth()->user();

        $data = $request->validate([
            'counterparty_name' => ['required', 'string', 'max:255'],
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

        $data['counterparty_name'] = trim((string) $data['counterparty_name']);

        if ($data['counterparty_name'] === '') {
            return back()
                ->withErrors([
                    'counterparty_name' => 'Debes escribir una contraparte.',
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
            $sequence = DocumentNumberGenerator::generate(
                tenantId: $tenant->id,
                kind: 'order.'.$data['group'],
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

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', "Orden creada correctamente con número {$order->number}.");
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $tenant = app('tenant');

        $supportsProductsModule = TenantModuleAccess::isEnabled(ModuleCatalog::PRODUCTS, $tenant);
        $supportsDocumentsModule = TenantModuleAccess::isEnabled(ModuleCatalog::DOCUMENTS, $tenant);

        $order->load([
            'creator',
            'updater',
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

        $navigationTrail = OrderNavigationTrail::edit($request, $order);

        return view('orders.edit', compact(
            'order',
            'navigationTrail',
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

        $security = app(Security::class);
        $user = auth()->user();

        $data = $request->validate([
            'counterparty_name' => ['required', 'string', 'max:255'],
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
            $order->loadMissing('items');

            if ($this->hasIncompleteInventoryItems($order)) {
                return back()
                    ->withErrors([
                        'status' => 'No se puede cerrar la orden mientras existan líneas pendientes o parciales.',
                    ])
                    ->withInput();
            }
        }

        $data['counterparty_name'] = trim((string) $data['counterparty_name']);

        if ($data['counterparty_name'] === '') {
            return back()
                ->withErrors([
                    'counterparty_name' => 'Debes escribir una contraparte.',
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
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
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
            $order->loadMissing('items');

            if ($this->hasIncompleteInventoryItems($order)) {
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
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
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
                'orders.index',
                NavigationTrail::toQuery($navigationTrail)
            )
            ->with('success', 'Orden eliminada.');
    }

    public function print(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
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
            $order->loadMissing('items');

            if ($this->hasIncompleteInventoryItems($order)) {
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

    protected function hasIncompleteInventoryItems(Order $order): bool
    {
        $order->loadMissing('items.product');

        return $order->items->contains(function ($item) {
            $product = $item->product;

            if (! $product || $product->kind !== 'product') {
                return false;
            }

            return ! in_array($item->status, ['completed', 'cancelled'], true);
        });
    }
}