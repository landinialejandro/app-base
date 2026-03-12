<?php

// FILE: app/Http/Controllers/DocumentController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\Document;
use App\Models\Order;
use App\Models\Party;

use App\Support\Catalogs\DocumentCatalog;

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
            'kind' => DocumentCatalog::KIND_QUOTE,
            'status' => DocumentCatalog::STATUS_DRAFT,
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
        $tenant = app('tenant');

        $data = $request->validate([
            'party_id' => [
                'nullable',
                'integer',
                Rule::exists('parties', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],

            'order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id);
                }),
            ],

            'kind' => [
                'required',
                Rule::in(DocumentCatalog::kinds()),
            ],

            'number' => ['nullable', 'string', 'max:255'],

            'status' => [
                'required',
                Rule::in(DocumentCatalog::statuses()),
            ],

            'issued_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();

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
        $tenant = app('tenant');

        $data = $request->validate([
            'party_id' => [
                'nullable',
                'integer',
                Rule::exists('parties', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],

            'order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id);
                }),
            ],

            'kind' => [
                'required',
                Rule::in(DocumentCatalog::kinds()),
            ],

            'number' => ['nullable', 'string', 'max:255'],

            'status' => [
                'required',
                Rule::in(DocumentCatalog::statuses()),
            ],

            'issued_at' => ['nullable', 'date'],
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
