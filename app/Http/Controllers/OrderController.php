<?php

// FILE: app/Http/Controllers/OrderController.php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Documents\DocumentNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['party', 'asset', 'items'])
            ->latest()
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function create(Request $request)
    {
        $tenant = app('tenant');

        $parties = Party::orderBy('name')->get();
        $assets = Asset::with('party')->orderBy('name')->get();

        $prefilledAsset = null;
        $prefilledPartyId = null;
        $fromAsset = false;
        $prefilledKind = old('kind', OrderCatalog::KIND_SALE);

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

        return view('orders.create', compact(
            'parties',
            'assets',
            'prefilledAsset',
            'prefilledPartyId',
            'prefilledKind',
            'fromAsset',
        ));
    }

    public function store(Request $request)
    {
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

        return redirect()
            ->route('orders.show', $order)
            ->with('success', "Orden creada correctamente con número {$order->number}.");
    }

    public function show(Order $order)
    {
        $order->load([
            'party',
            'asset',
            'creator',
            'updater',
            'items.product',
            'documents',
        ]);

        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $parties = Party::orderBy('name')->get();
        $assets = Asset::with('party')->orderBy('name')->get();

        return view('orders.edit', compact('order', 'parties', 'assets'));
    }

    public function update(Request $request, Order $order)
    {
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

        // Seguridad: si la orden ya está vinculada a un activo, no se permite
        // cambiar ni el activo ni el contacto asociado.
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

        // Si la orden aún no tenía activo, pero ahora se selecciona uno,
        // debe corresponder al contacto elegido.
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

        $order->update($data);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Orden actualizada.');
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()
            ->route('orders.index')
            ->with('success', 'Orden eliminada.');
    }
}
