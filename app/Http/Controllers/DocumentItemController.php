<?php

// FILE: app/Http/Controllers/DocumentItemController.php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Product;
use Illuminate\Http\Request;

class DocumentItemController extends Controller
{
    public function create(Document $document)
    {
        $products = Product::orderBy('name')->get();

        $item = new DocumentItem([
            'kind' => 'product',
            'quantity' => 1,
            'unit_price' => 0,
        ]);

        return view('documents.items.create', compact('document', 'item', 'products'));
    }

    public function store(Request $request, Document $document)
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'position' => ['nullable', 'integer', 'min:1'],
            'kind' => ['required', 'in:product,service'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['tenant_id'] = $document->tenant_id;
        $data['document_id'] = $document->id;
        $data['position'] = $data['position'] ?? (($document->items()->max('position') ?? 0) + 1);

        $quantity = (float) $data['quantity'];
        $unitPrice = (float) $data['unit_price'];
        $data['line_total'] = $quantity * $unitPrice;

        $document->items()->create($data);

        $this->recalculateTotals($document);

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

        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'position' => ['nullable', 'integer', 'min:1'],
            'kind' => ['required', 'in:product,service'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $quantity = (float) $data['quantity'];
        $unitPrice = (float) $data['unit_price'];
        $data['line_total'] = $quantity * $unitPrice;

        $item->update($data);

        $this->recalculateTotals($document);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Document $document, DocumentItem $item)
    {
        abort_unless($item->document_id === $document->id, 404);

        $item->delete();

        $this->recalculateTotals($document);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Ítem eliminado correctamente.');
    }

    protected function recalculateTotals(Document $document): void
    {
        $subtotal = (float) $document->items()->sum('line_total');
        $taxTotal = 0;
        $total = $subtotal + $taxTotal;

        $document->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
        ]);
    }
}