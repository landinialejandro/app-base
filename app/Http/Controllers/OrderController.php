<?php

// FILE: app/Http/Controllers/OrderController.php | V25

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Models\Task;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Inventory\OrderInventoryContextResolver;
use App\Support\Navigation\AppointmentNavigationTrail;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
use App\Support\Navigation\TaskNavigationTrail;
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

        $tenant = app('tenant');
        $security = app(Security::class);
        $user = auth()->user();

        $supportsAssetsModule = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);

        $q = trim((string) $request->get('q', ''));
        $partyId = $request->get('party_id');
        $assetId = $supportsAssetsModule ? $request->get('asset_id') : null;
        $kind = $request->get('kind');
        $status = $request->get('status');
        $orderedAt = $request->get('ordered_at');

        $parties = $security
            ->scope($user, 'parties.viewAny', Party::query())
            ->orderBy('name')
            ->get();

        $assets = $supportsAssetsModule
            ? $security
                ->scope($user, 'assets.viewAny', Asset::query())
                ->with('party')
                ->orderBy('name')
                ->get()
            : collect();

        $orders = $security
            ->scope($user, 'orders.viewAny', Order::query())
            ->with(['party', 'asset', 'task', 'items'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('number', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($partyId, function ($query) use ($partyId) {
                $query->where('party_id', $partyId);
            })
            ->when($supportsAssetsModule && $assetId, function ($query) use ($assetId) {
                $query->where('asset_id', $assetId);
            })
            ->when($kind, function ($query) use ($kind) {
                $query->where('kind', $kind);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($orderedAt, function ($query) use ($orderedAt) {
                $query->whereDate('ordered_at', $orderedAt);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $allowedCreateKinds = collect(OrderCatalog::kinds())
            ->filter(fn (string $orderKind) => $security->allows(
                $user,
                'orders.create',
                Order::class,
                ['kind' => $orderKind]
            ))
            ->values();

        $canCreateOrders = $allowedCreateKinds->isNotEmpty();
        $defaultCreateKind = $allowedCreateKinds->first();

        return view('orders.index', compact(
            'orders',
            'parties',
            'assets',
            'supportsAssetsModule',
            'canCreateOrders',
            'defaultCreateKind',
        ));
    }

    public function create(Request $request)
    {
        $tenant = app('tenant');
        $security = app(Security::class);
        $user = auth()->user();

        $supportsAssetsModule = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);

        $parties = $security
            ->scope($user, 'parties.viewAny', Party::query())
            ->orderBy('name')
            ->get();

        $assets = $supportsAssetsModule
            ? $security
                ->scope($user, 'assets.viewAny', Asset::query())
                ->with('party')
                ->orderBy('name')
                ->get()
            : collect();

        $prefilledAsset = null;
        $prefilledPartyId = null;
        $fromAsset = false;
        $prefilledKind = old('kind', OrderCatalog::KIND_SALE);
        $prefilledTask = null;
        $prefilledAppointment = null;

        $requestedKind = (string) $request->get('kind', '');

        if ($requestedKind !== '' && in_array($requestedKind, OrderCatalog::kinds(), true)) {
            $prefilledKind = $requestedKind;
        }

        if ($supportsAssetsModule && $request->filled('asset_id')) {
            $prefilledAsset = $security
                ->scope($user, 'assets.viewAny', Asset::query())
                ->where('id', $request->integer('asset_id'))
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->first();

            if ($prefilledAsset) {
                $prefilledPartyId = $prefilledAsset->party_id;
                $fromAsset = true;
                $prefilledKind = OrderCatalog::KIND_SERVICE;
            }
        }

        if ($request->filled('task_id')) {
            $prefilledTask = $security
                ->scope($user, 'tasks.viewAny', Task::query())
                ->with('order')
                ->where('id', $request->integer('task_id'))
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->firstOrFail();

            if ($prefilledTask->order) {
                $orderTrail = OrderNavigationTrail::show($request, $prefilledTask->order, task: $prefilledTask);

                return redirect()
                    ->route('orders.show', ['order' => $prefilledTask->order] + NavigationTrail::toQuery($orderTrail))
                    ->with('success', 'La tarea ya tiene una orden asociada.');
            }

            if ($prefilledTask->party_id) {
                $prefilledPartyId = $prefilledTask->party_id;
            }

            $prefilledKind = OrderCatalog::KIND_SERVICE;
        }

        if ($request->filled('appointment_id')) {
            $prefilledAppointment = $security
                ->scope($user, 'appointments.viewAny', Appointment::query())
                ->with('order')
                ->where('id', $request->integer('appointment_id'))
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->firstOrFail();

            if ($prefilledAppointment->order) {
                $orderTrail = OrderNavigationTrail::show($request, $prefilledAppointment->order, appointment: $prefilledAppointment);

                return redirect()
                    ->route('orders.show', ['order' => $prefilledAppointment->order] + NavigationTrail::toQuery($orderTrail))
                    ->with('success', 'El turno ya tiene una orden asociada.');
            }

            if ($prefilledAppointment->party_id) {
                $prefilledPartyId = $prefilledAppointment->party_id;
            }

            if ($supportsAssetsModule && $prefilledAppointment->asset_id) {
                $prefilledAsset = $security
                    ->scope($user, 'assets.viewAny', Asset::query())
                    ->where('id', $prefilledAppointment->asset_id)
                    ->where('tenant_id', $tenant->id)
                    ->whereNull('deleted_at')
                    ->first();
            }

            $prefilledKind = OrderCatalog::KIND_SERVICE;
        }

        $security->authorize(
            $user,
            'orders.create',
            Order::class,
            ['kind' => $prefilledKind]
        );

        $navigationTrail = OrderNavigationTrail::create(
            $request,
            appointment: $prefilledAppointment,
            task: $prefilledTask,
        );

        return view('orders.create', compact(
            'parties',
            'assets',
            'prefilledAsset',
            'prefilledPartyId',
            'prefilledKind',
            'fromAsset',
            'prefilledTask',
            'prefilledAppointment',
            'navigationTrail',
            'supportsAssetsModule',
        ));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');
        $security = app(Security::class);
        $user = auth()->user();

        $data = $request->validate([
            'party_id' => [
                'required',
                'integer',
                Rule::exists('parties', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'task_id' => [
                'nullable',
                'integer',
                Rule::exists('tasks', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
                Rule::unique('orders', 'task_id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'kind' => [
                'required',
                Rule::in(OrderCatalog::kinds()),
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
            ['kind' => $data['kind']]
        );

        if (! empty($data['asset_id'])) {
            $asset = $security
                ->scope($user, 'assets.viewAny', Asset::query())
                ->whereKey($data['asset_id'])
                ->firstOrFail();

            if ((int) $asset->party_id !== (int) $data['party_id']) {
                return back()
                    ->withErrors([
                        'asset_id' => 'El activo seleccionado pertenece a otro contacto.',
                    ])
                    ->withInput();
            }
        }

        if (! empty($data['task_id'])) {
            $security
                ->scope($user, 'tasks.viewAny', Task::query())
                ->whereKey($data['task_id'])
                ->firstOrFail();
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

        $appointment = null;
        $task = null;

        $appointmentId = $data['appointment_id'] ?? null;
        unset($data['appointment_id']);

        $taskId = $data['task_id'] ?? null;

        $order = DB::transaction(function () use ($tenant, $data) {
            $sequence = DocumentNumberGenerator::generate(
                tenantId: $tenant->id,
                kind: 'order.'.$data['kind'],
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

        if (! empty($appointmentId)) {
            $appointment = $security
                ->scope($user, 'appointments.viewAny', Appointment::query())
                ->where('id', $appointmentId)
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->firstOrFail();

            if (empty($appointment->order_id)) {
                $appointment->update([
                    'order_id' => $order->id,
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        if (! empty($taskId)) {
            $task = $security
                ->scope($user, 'tasks.viewAny', Task::query())
                ->where('id', $taskId)
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->first();
        }

        $navigationTrail = OrderNavigationTrail::show(
            $request,
            $order,
            appointment: $appointment,
            task: $task,
        );

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', "Orden creada correctamente con número {$order->number}.");
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $tenant = app('tenant');

        $supportsAssetsModule = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);
        $supportsProductsModule = TenantModuleAccess::isEnabled(ModuleCatalog::PRODUCTS, $tenant);
        $supportsDocumentsModule = TenantModuleAccess::isEnabled(ModuleCatalog::DOCUMENTS, $tenant);
        $supportsTasksModule = TenantModuleAccess::isEnabled(ModuleCatalog::TASKS, $tenant);

        $order->load([
            'party',
            'asset',
            'task',
            'creator',
            'updater',
            'items.product',
            'items.inventoryMovements',
            'documents',
            'inventoryMovements.product',
            'inventoryMovements.orderItem',
            'attachments' => fn ($query) => $query->ordered(),
        ]);

        $inventoryProducts = $supportsProductsModule
            ? $order->items
                ->filter(fn ($item) => $item->product && $item->product->kind === ProductCatalog::KIND_PRODUCT)
                ->map(fn ($item) => $item->product)
                ->unique('id')
                ->sortBy('name')
                ->values()
            : collect();

        $inventoryContext = $supportsProductsModule
            ? app(OrderInventoryContextResolver::class)->forOrder($order)
            : null;

        $appointment = AppointmentNavigationTrail::resolveFromRequest($request, $order->tenant_id);
        $task = TaskNavigationTrail::resolveFromRequest($request, $order->tenant_id);

        $navigationTrail = OrderNavigationTrail::show(
            $request,
            $order,
            appointment: $appointment,
            task: $task,
        );

        return view('orders.show', compact(
            'order',
            'navigationTrail',
            'inventoryProducts',
            'inventoryContext',
            'supportsAssetsModule',
            'supportsProductsModule',
            'supportsDocumentsModule',
            'supportsTasksModule',
        ));
    }

    public function edit(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $tenant = app('tenant');
        $security = app(Security::class);
        $user = auth()->user();

        $supportsAssetsModule = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);

        $parties = $security
            ->scope($user, 'parties.viewAny', Party::query())
            ->orderBy('name')
            ->get();

        $assets = $supportsAssetsModule
            ? $security
                ->scope($user, 'assets.viewAny', Asset::query())
                ->with('party')
                ->orderBy('name')
                ->get()
            : collect();

        $appointment = AppointmentNavigationTrail::resolveFromRequest($request, $order->tenant_id);
        $task = TaskNavigationTrail::resolveFromRequest($request, $order->tenant_id);

        $navigationTrail = OrderNavigationTrail::edit(
            $request,
            $order,
            appointment: $appointment,
            task: $task,
        );

        return view('orders.edit', compact(
            'order',
            'parties',
            'assets',
            'navigationTrail',
            'supportsAssetsModule',
        ));
    }

    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $tenant = app('tenant');
        $security = app(Security::class);
        $user = auth()->user();

        $supportsAssetsModule = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);

        $data = $request->validate([
            'party_id' => [
                'required',
                'integer',
                Rule::exists('parties', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'task_id' => [
                'nullable',
                'integer',
                Rule::exists('tasks', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
                Rule::unique('orders', 'task_id')
                    ->ignore($order->id)
                    ->where(function ($query) use ($tenant) {
                        $query->where('tenant_id', $tenant->id)
                            ->whereNull('deleted_at');
                    }),
            ],
            'kind' => [
                'required',
                Rule::in(OrderCatalog::kinds()),
            ],
            'status' => [
                'required',
                Rule::in(OrderCatalog::statuses()),
            ],
            'ordered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($order->number) {
            $data['kind'] = $order->kind;
        }

        if (! $supportsAssetsModule || ! array_key_exists('asset_id', $data)) {
            $data['asset_id'] = $order->asset_id;
        }

        $security->authorize(
            $user,
            'orders.update',
            $order,
            ['kind' => $order->kind]
        );

        if (! OrderCatalog::canTransition($order->status, $data['status'])) {
            return back()
                ->withErrors([
                    'status' => 'La transición de estado solicitada no es válida.',
                ])
                ->withInput();
        }

        if ($data['status'] === OrderCatalog::STATUS_CANCELLED && $order->hasInventoryMovements()) {
            return back()
                ->withErrors([
                    'status' => 'No se puede cancelar una orden con movimientos registrados. Primero deben revertirse.',
                ])
                ->withInput();
        }

        if ($data['status'] === OrderCatalog::STATUS_CLOSED) {
            $order->loadMissing('items');

            $hasIncompleteItems = $order->items->contains(function ($item) {
                return ! in_array($item->status, ['completed', 'cancelled'], true);
            });

            if ($hasIncompleteItems) {
                return back()
                    ->withErrors([
                        'status' => 'No se puede cerrar la orden mientras existan líneas pendientes o parciales.',
                    ])
                    ->withInput();
            }
        }

        if (! empty($order->asset_id)) {
            if ((int) $data['asset_id'] !== (int) $order->asset_id) {
                return back()
                    ->withErrors([
                        'asset_id' => 'No se puede cambiar el activo de una orden ya vinculada.',
                    ])
                    ->withInput();
            }

            if ((int) ($data['party_id'] ?? 0) !== (int) $order->party_id) {
                return back()
                    ->withErrors([
                        'party_id' => 'No se puede cambiar el contacto de una orden ya vinculada a un activo.',
                    ])
                    ->withInput();
            }
        }

        if (empty($order->asset_id) && ! empty($data['asset_id'])) {
            $asset = $security
                ->scope($user, 'assets.viewAny', Asset::query())
                ->whereKey($data['asset_id'])
                ->firstOrFail();

            if ((int) $asset->party_id !== (int) $data['party_id']) {
                return back()
                    ->withErrors([
                        'asset_id' => 'El activo seleccionado pertenece a otro contacto.',
                    ])
                    ->withInput();
            }
        }

        if (! empty($data['task_id'])) {
            $security
                ->scope($user, 'tasks.viewAny', Task::query())
                ->whereKey($data['task_id'])
                ->firstOrFail();
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

        if (! array_key_exists('task_id', $data)) {
            $data['task_id'] = $order->task_id;
        }

        $order->update($data);

        $appointment = AppointmentNavigationTrail::resolveFromRequest($request, $order->tenant_id);
        $task = TaskNavigationTrail::resolveFromRequest($request, $order->tenant_id);

        $navigationTrail = OrderNavigationTrail::show(
            $request,
            $order,
            appointment: $appointment,
            task: $task,
        );

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Orden actualizada.');
    }

    public function destroy(Request $request, Order $order)
    {
        $this->authorize('delete', $order);

        if ($order->hasInventoryMovements()) {
            return redirect()
                ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery(
                    OrderNavigationTrail::show($request, $order)
                ))
                ->withErrors([
                    'order' => 'No se puede eliminar una orden con movimientos de inventory registrados.',
                ]);
        }

        $appointment = AppointmentNavigationTrail::resolveFromRequest($request, $order->tenant_id);
        $task = TaskNavigationTrail::resolveFromRequest($request, $order->tenant_id);

        $navigationTrail = OrderNavigationTrail::show(
            $request,
            $order,
            appointment: $appointment,
            task: $task,
        );

        $redirectUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.index'));

        $order->delete();

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Orden eliminada.');
    }

    public function print(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'party',
            'asset',
            'task',
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
            'asset',
            'task',
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
}
