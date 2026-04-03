<?php

// FILE: app/Http/Controllers/OrderController.php | V15

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Models\Task;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Navigation\AppointmentNavigationTrail;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\OrderNavigationTrail;
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

        $requestedKind = (string) $request->get('kind', '');

        if ($requestedKind !== '' && in_array($requestedKind, OrderCatalog::kinds(), true)) {
            $prefilledKind = $requestedKind;
        }

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
                $orderTrail = OrderNavigationTrail::show($request, $prefilledTask->order);

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
                $orderTrail = OrderNavigationTrail::show($request, $prefilledAppointment->order, $prefilledAppointment);

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

        $navigationTrail = OrderNavigationTrail::create($request, $prefilledAppointment);

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

        $navigationTrail = OrderNavigationTrail::show($request, $order, $appointment);

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
            'inventoryMovements.product',
            'attachments' => fn ($query) => $query->ordered(),
        ]);

        $inventoryProducts = $order->items
            ->filter(fn ($item) => $item->product && $item->product->kind === ProductCatalog::KIND_PRODUCT)
            ->map(fn ($item) => $item->product)
            ->unique('id')
            ->sortBy('name')
            ->values();

        $appointment = AppointmentNavigationTrail::resolveFromRequest($request, $order->tenant_id);
        $navigationTrail = OrderNavigationTrail::show($request, $order, $appointment);

        return view('orders.show', compact(
            'order',
            'navigationTrail',
            'inventoryProducts',
        ));
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

        $appointment = AppointmentNavigationTrail::resolveFromRequest($request, $order->tenant_id);
        $navigationTrail = OrderNavigationTrail::edit($request, $order, $appointment);

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

        $appointment = AppointmentNavigationTrail::resolveFromRequest($request, $order->tenant_id);
        $navigationTrail = OrderNavigationTrail::show($request, $order, $appointment);

        return redirect()
            ->route('orders.show', ['order' => $order] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Orden actualizada.');
    }

    public function destroy(Request $request, Order $order)
    {
        $this->authorize('delete', $order);

        $appointment = AppointmentNavigationTrail::resolveFromRequest($request, $order->tenant_id);
        $navigationTrail = OrderNavigationTrail::show($request, $order, $appointment);
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
