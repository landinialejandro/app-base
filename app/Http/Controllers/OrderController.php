<?php

// FILE: app/Http/Controllers/OrderController.php | V9

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Models\Task;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Navigation\NavigationTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $q = trim((string) $request->get('q', ''));
        $partyId = $request->get('party_id');
        $assetId = $request->get('asset_id');
        $kind = $request->get('kind');
        $status = $request->get('status');
        $orderedAt = $request->get('ordered_at');

        $parties = Party::query()
            ->orderBy('name')
            ->get();

        $assets = Asset::query()
            ->with('party')
            ->orderBy('name')
            ->get();

        $orders = Order::query()
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
            ->when($assetId, function ($query) use ($assetId) {
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

        return view('orders.index', compact('orders', 'parties', 'assets'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Order::class);

        $tenant = app('tenant');

        $parties = Party::query()
            ->orderBy('name')
            ->get();

        $assets = Asset::query()
            ->with('party')
            ->orderBy('name')
            ->get();

        $prefilledAsset = null;
        $prefilledPartyId = null;
        $fromAsset = false;
        $prefilledKind = old('kind', OrderCatalog::KIND_SALE);
        $prefilledTask = null;
        $prefilledAppointment = null;

        $navigationTrail = NavigationTrail::fromRequest($request);

        if ($request->filled('asset_id')) {
            $prefilledAsset = Asset::query()
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
            $prefilledTask = Task::query()
                ->with('order')
                ->where('id', $request->integer('task_id'))
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->firstOrFail();

            if ($prefilledTask->order) {
                $orderTrail = $this->buildOrderShowTrail($request, $prefilledTask->order);

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
            $prefilledAppointment = Appointment::query()
                ->with('order')
                ->where('id', $request->integer('appointment_id'))
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->firstOrFail();

            if ($prefilledAppointment->order) {
                $orderTrail = $this->buildOrderShowTrail($request, $prefilledAppointment->order, $prefilledAppointment);

                return redirect()
                    ->route('orders.show', ['order' => $prefilledAppointment->order] + NavigationTrail::toQuery($orderTrail))
                    ->with('success', 'El turno ya tiene una orden asociada.');
            }

            if ($prefilledAppointment->party_id) {
                $prefilledPartyId = $prefilledAppointment->party_id;
            }

            if ($prefilledAppointment->asset_id) {
                $prefilledAsset = Asset::query()
                    ->where('id', $prefilledAppointment->asset_id)
                    ->where('tenant_id', $tenant->id)
                    ->whereNull('deleted_at')
                    ->first();
            }

            $prefilledKind = OrderCatalog::KIND_SERVICE;
        }

        $navigationTrail = $this->buildOrderCreateTrail($request, $prefilledAppointment);

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
        ));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Order::class);

        $tenant = app('tenant');
        $navigationTrail = NavigationTrail::fromRequest($request);

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

        if (! empty($data['asset_id'])) {
            $asset = Asset::query()->findOrFail($data['asset_id']);

            if ((int) $asset->party_id !== (int) $data['party_id']) {
                return back()
                    ->withErrors([
                        'asset_id' => 'El activo seleccionado pertenece a otro contacto.',
                    ])
                    ->withInput();
            }
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
        $appointmentId = $data['appointment_id'] ?? null;
        unset($data['appointment_id']);

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
            $appointment = Appointment::query()
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

        $navigationTrail = $this->buildOrderShowTrail($request, $order, $appointment);

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', "Orden creada correctamente con número {$order->number}.");
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'party',
            'asset',
            'task',
            'creator',
            'updater',
            'items.product',
            'documents',
        ]);

        $navigationTrail = $this->buildOrderShowTrail($request, $order);

        return view('orders.show', compact('order', 'navigationTrail'));
    }

    public function edit(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $parties = Party::query()
            ->orderBy('name')
            ->get();

        $assets = Asset::query()
            ->with('party')
            ->orderBy('name')
            ->get();

        $navigationTrail = $this->buildOrderEditTrail($request, $order);

        return view('orders.edit', compact('order', 'parties', 'assets', 'navigationTrail'));
    }

    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $tenant = app('tenant');

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

        if ($order->number && $data['kind'] !== $order->kind) {
            return back()
                ->withErrors([
                    'kind' => 'No se puede cambiar el tipo de una orden que ya fue numerada.',
                ])
                ->withInput();
        }

        if (! empty($order->asset_id)) {
            if ((int) ($data['asset_id'] ?? 0) !== (int) $order->asset_id) {
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

        if (! empty($data['asset_id'])) {
            $asset = Asset::query()->findOrFail($data['asset_id']);

            if ((int) $asset->party_id !== (int) $data['party_id']) {
                return back()
                    ->withErrors([
                        'asset_id' => 'El activo seleccionado pertenece a otro contacto.',
                    ])
                    ->withInput();
            }
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

        $navigationTrail = $this->buildOrderShowTrail($request, $order);

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Orden actualizada.');
    }

    public function destroy(Request $request, Order $order)
    {
        $this->authorize('delete', $order);

        $navigationTrail = $this->buildOrderShowTrail($request, $order);
        $redirectUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.index'));

        $order->delete();

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Orden eliminada.');
    }

    protected function buildOrderCreateTrail(Request $request, ?Appointment $appointment = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            if ($appointment) {
                $trail = $this->appointmentBaseTrail($appointment);
            } else {
                $trail = $this->ordersBaseTrail();
            }
        }

        $trail = NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'orders.create',
                'new',
                'Nueva orden',
                route('orders.create')
            )
        );

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('orders.create', NavigationTrail::toQuery($trail))
        );
    }

    protected function buildOrderShowTrail(Request $request, Order $order, ?Appointment $appointment = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $appointment = $appointment ?: $this->resolveAppointmentFromRequest($request, $order->tenant_id);

            if ($appointment) {
                $trail = $this->appointmentBaseTrail($appointment);
            } else {
                $trail = $this->ordersBaseTrail();
            }
        }

        $trail = NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'orders.show',
                $order->id,
                $order->number ?: 'Orden #'.$order->id,
                route('orders.show', ['order' => $order])
            )
        );

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('orders.show', ['order' => $order] + NavigationTrail::toQuery($trail))
        );
    }

    protected function buildOrderEditTrail(Request $request, Order $order): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'orders.show', $order->id)) {
            $trail = $this->buildOrderShowTrail($request, $order);
        }

        $trail = NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'orders.edit',
                $order->id,
                'Editar',
                route('orders.edit', ['order' => $order])
            )
        );

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('orders.edit', ['order' => $order] + NavigationTrail::toQuery($trail))
        );
    }

    protected function ordersBaseTrail(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('orders.index', null, 'Órdenes', route('orders.index')),
        ]);
    }

    protected function appointmentBaseTrail(Appointment $appointment): array
    {
        $trail = NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('appointments.index', null, 'Turnos', route('appointments.index')),
            NavigationTrail::makeNode(
                'appointments.show',
                $appointment->id,
                $appointment->title ?: 'Turno #'.$appointment->id,
                route('appointments.show', ['appointment' => $appointment])
            ),
        ]);

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('appointments.show', ['appointment' => $appointment] + NavigationTrail::toQuery($trail))
        );
    }

    protected function resolveAppointmentFromRequest(Request $request, string $tenantId): ?Appointment
    {
        $appointmentId = $request->integer('appointment_id');

        if ($appointmentId <= 0) {
            return null;
        }

        return Appointment::query()
            ->where('id', $appointmentId)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first();
    }
}
