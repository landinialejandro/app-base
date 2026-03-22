<?php

// FILE: app/Http/Controllers/DocumentItemController.php | V3

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Product;
use App\Support\Documents\DocumentTotalsCalculator;
use App\Support\Navigation\NavigationContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentItemController extends Controller
{
    public function create(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $products = Product::query()
            ->orderBy('name')
            ->get();

        $item = new DocumentItem([
            'position' => ((int) $document->items()->max('position')) + 1,
            'quantity' => 1,
            'unit_price' => null,
        ]);

        $navigationContext = NavigationContext::resolveFromRequest($request, $document->tenant_id);

        return view('documents.items.create', compact('document', 'item', 'products', 'navigationContext'));
    }

    public function store(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $navigationContext = NavigationContext::resolveFromRequest($request, $document->tenant_id);

        $data = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($document) {
                    $query->where('tenant_id', $document->tenant_id)
                        ->whereNull('deleted_at');
                }),
            ],
            'position' => ['required', 'integer', 'min:1'],
            'kind' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['tenant_id'] = $document->tenant_id;
        $data['document_id'] = $document->id;
        $data['line_total'] = (float) $data['quantity'] * (float) $data['unit_price'];

        DocumentItem::create($data);

        DocumentTotalsCalculator::apply($document->fresh());

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationContext::routeParams($navigationContext))
            ->with('success', 'Ítem agregado correctamente.');
    }

    public function edit(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $products = Product::query()
            ->orderBy('name')
            ->get();

        $navigationContext = NavigationContext::resolveFromRequest($request, $document->tenant_id);

        return view('documents.items.edit', compact('document', 'item', 'products', 'navigationContext'));
    }

    public function update(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $navigationContext = NavigationContext::resolveFromRequest($request, $document->tenant_id);

        $data = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($document) {
                    $query->where('tenant_id', $document->tenant_id)
                        ->whereNull('deleted_at');
                }),
            ],
            'position' => ['required', 'integer', 'min:1'],
            'kind' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['line_total'] = (float) $data['quantity'] * (float) $data['unit_price'];

        $item->update($data);

        DocumentTotalsCalculator::apply($document->fresh());

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationContext::routeParams($navigationContext))
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $navigationContext = NavigationContext::resolveFromRequest($request, $document->tenant_id);

        $item->delete();

        DocumentTotalsCalculator::apply($document->fresh());

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationContext::routeParams($navigationContext))
            ->with('success', 'Ítem eliminado correctamente.');
    }
}
