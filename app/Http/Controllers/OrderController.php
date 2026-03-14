<?php

// FILE: app/Http/Controllers/OrderController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

use App\Models\Order;
use App\Models\Party;

use App\Support\Catalogs\OrderCatalog;
use App\Support\Documents\DocumentNumberGenerator;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['party', 'items'])
            ->latest()
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $parties = Party::orderBy('name')->get();

        return view('orders.create', compact('parties'));
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
                kind: 'order.' . $data['kind'],
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

        return view('orders.edit', compact('order', 'parties'));
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