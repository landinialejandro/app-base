<?php

// FILE: app/Http/Controllers/DocumentItemController.php | V8

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Product;
use App\Support\Auth\Security;
use App\Support\Documents\DocumentTotalsCalculator;
use App\Support\Navigation\DocumentNavigationTrail;
use App\Support\Navigation\NavigationTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentItemController extends Controller
{
    public function create(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $security = app(Security::class);
        $user = auth()->user();

        $products = $security
            ->scope($user, 'products.viewAny', Product::query())
            ->where('tenant_id', $document->tenant_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $item = new DocumentItem([
            'position' => ((int) $document->items()->max('position')) + 1,
            'quantity' => 1,
            'unit_price' => null,
        ]);

        $navigationTrail = DocumentNavigationTrail::itemCreate($request, $document);

        return view('documents.items.create', compact('document', 'item', 'products', 'navigationTrail'));
    }

    public function store(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $security = app(Security::class);
        $user = auth()->user();

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

        if (! empty($data['product_id'])) {
            $security
                ->scope($user, 'products.viewAny', Product::query())
                ->where('tenant_id', $document->tenant_id)
                ->whereNull('deleted_at')
                ->whereKey($data['product_id'])
                ->firstOrFail();
        }

        $data['tenant_id'] = $document->tenant_id;
        $data['document_id'] = $document->id;
        $data['line_total'] = (float) $data['quantity'] * (float) $data['unit_price'];

        DocumentItem::create($data);

        DocumentTotalsCalculator::apply($document->fresh());

        $navigationTrail = DocumentNavigationTrail::show($request, $document);

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem agregado correctamente.');
    }

    public function edit(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $security = app(Security::class);
        $user = auth()->user();

        $products = $security
            ->scope($user, 'products.viewAny', Product::query())
            ->where('tenant_id', $document->tenant_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $navigationTrail = DocumentNavigationTrail::itemEdit($request, $document, $item);

        return view('documents.items.edit', compact('document', 'item', 'products', 'navigationTrail'));
    }

    public function update(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $security = app(Security::class);
        $user = auth()->user();

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

        if (! empty($data['product_id'])) {
            $security
                ->scope($user, 'products.viewAny', Product::query())
                ->where('tenant_id', $document->tenant_id)
                ->whereNull('deleted_at')
                ->whereKey($data['product_id'])
                ->firstOrFail();
        }

        $data['line_total'] = (float) $data['quantity'] * (float) $data['unit_price'];

        $item->update($data);

        DocumentTotalsCalculator::apply($document->fresh());

        $navigationTrail = DocumentNavigationTrail::show($request, $document);

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $item->delete();

        DocumentTotalsCalculator::apply($document->fresh());

        $navigationTrail = DocumentNavigationTrail::show($request, $document);

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem eliminado correctamente.');
    }
}
