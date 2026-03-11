<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Party;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Listado
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $orders = Order::with(['party', 'items'])
            ->latest()
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $parties = Party::orderBy('name')->get();

        return view('orders.create', compact('parties'));
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $data = $request->validate([
            'party_id' => ['nullable', 'exists:parties,id'],
            'kind' => ['required', 'in:sale,purchase,service'],
            'number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,confirmed,cancelled'],
            'ordered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();

        $order = Order::create($data);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Orden creada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar
    |--------------------------------------------------------------------------
    */

    public function show(Order $order)
    {
        $order->load([
            'party',
            'creator',
            'updater',
            'items.product',
        ]);

        return view('orders.show', compact('order'));
    }

    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    public function edit(Order $order)
    {
        $parties = Party::orderBy('name')->get();

        return view('orders.edit', compact('order', 'parties'));
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'party_id' => ['nullable', 'exists:parties,id'],
            'kind' => ['required', 'in:sale,purchase,service'],
            'number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,confirmed,cancelled'],
            'ordered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['updated_by'] = auth()->id();

        $order->update($data);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Orden actualizada.');
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()
            ->route('orders.index')
            ->with('success', 'Orden eliminada.');
    }
}