<?php

// FILE: app/Http/Controllers/DocumentController.php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Listado
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $documents = Document::with(['party', 'order', 'items'])
            ->latest()
            ->paginate(20);

        return view('documents.index', compact('documents'));
    }

    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $parties = Party::orderBy('name')->get();
        $orders = Order::latest()->get();

        $document = new Document([
            'kind' => 'quote',
            'status' => 'draft',
        ]);

        return view('documents.create', compact('document', 'parties', 'orders'));
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
            'order_id' => ['nullable', 'exists:orders,id'],
            'kind' => ['required', 'in:quote,invoice,delivery_note,work_order,receipt,credit_note'],
            'number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,issued,cancelled'],
            'issued_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();
        $data['subtotal'] = 0;
        $data['tax_total'] = 0;
        $data['total'] = 0;

        $document = Document::create($data);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Documento creado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar
    |--------------------------------------------------------------------------
    */

    public function show(Document $document)
    {
        $document->load([
            'party',
            'order',
            'creator',
            'updater',
            'items.product',
        ]);

        return view('documents.show', compact('document'));
    }

    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    public function edit(Document $document)
    {
        $parties = Party::orderBy('name')->get();
        $orders = Order::latest()->get();

        return view('documents.edit', compact('document', 'parties', 'orders'));
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Document $document)
    {
        $data = $request->validate([
            'party_id' => ['nullable', 'exists:parties,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'kind' => ['required', 'in:quote,invoice,delivery_note,work_order,receipt,credit_note'],
            'number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,issued,cancelled'],
            'issued_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['updated_by'] = auth()->id();

        $document->update($data);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Documento actualizado.');
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function destroy(Document $document)
    {
        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Documento eliminado.');
    }
}