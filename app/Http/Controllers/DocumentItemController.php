<?php

// FILE: app/Http/Controllers/DocumentItemController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Product;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Documents\DocumentTotalsCalculator;

class DocumentItemController extends Controller
{
    public function create(Document $document)
    {
        $products = Product::orderBy('name')->get();

        $item = new DocumentItem([
            'kind' => ProductCatalog::KIND_PRODUCT,
            'quantity' => 1,
            'unit_price' => 0,
        ]);

        return view('documents.items.create', compact('document', 'item', 'products'));
    }

    public function store(Request $request, Document $document)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'position' => ['nullable', 'integer', 'min:1'],
            'kind' => [
                'required',
                Rule::in(ProductCatalog::kinds()),
            ],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['position'] = $data['position'] ?? (($document->items()->max('position') ?? 0) + 1);

        $quantity = (float) $data['quantity'];
        $unitPrice = (float) $data['unit_price'];
        $data['line_total'] = $quantity * $unitPrice;

        $item = new DocumentItem($data);
        $item->tenant_id = $document->tenant_id;
        $item->document_id = $document->id;
        $item->save();

        DocumentTotalsCalculator::apply($document);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Ítem agregado correctamente.');
    }

    public function edit(Document $document, DocumentItem $item)
    {
        abort_unless($item->document_id === $document->id, 404);

        $products = Product::orderBy('name')->get();

        return view('documents.items.edit', compact('document', 'item', 'products'));
    }

    public function update(Request $request, Document $document, DocumentItem $item)
    {
        abort_unless($item->document_id === $document->id, 404);

        $tenant = app('tenant');

        $data = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'position' => ['nullable', 'integer', 'min:1'],
            'kind' => [
                'required',
                Rule::in(ProductCatalog::kinds()),
            ],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $quantity = (float) $data['quantity'];
        $unitPrice = (float) $data['unit_price'];
        $data['line_total'] = $quantity * $unitPrice;

        $item->update($data);

        DocumentTotalsCalculator::apply($document);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Document $document, DocumentItem $item)
    {
        abort_unless($item->document_id === $document->id, 404);

        $item->delete();

        DocumentTotalsCalculator::apply($document);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Ítem eliminado correctamente.');
    }
}