<?php

// FILE: app/Http/Controllers/DocumentItemController.php | V15

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Support\Catalogs\DocumentCatalog;
use App\Support\Catalogs\DocumentItemCatalog;
use App\Support\Documents\DocumentsHooks;
use App\Support\Documents\DocumentTotalsCalculator;
use App\Support\LineItems\LineItemMath;
use App\Support\LineItems\LineItemValidationRules;
use App\Support\Navigation\DocumentNavigationTrail;
use App\Support\Navigation\NavigationTrail;
use App\Support\Products\ProductLineItemSelector;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DocumentItemController extends Controller
{
    public function create(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $products = app(ProductLineItemSelector::class)->optionsFor(
            user: auth()->user(),
            tenantId: (string) $document->tenant_id,
            enabled: true,
        );

        $item = new DocumentItem([
            'position' => ((int) $document->items()->max('position')) + 1,
            'quantity' => 1,
            'status' => DocumentItemCatalog::STATUS_PENDING,
            'unit_price' => null,
        ]);

        $navigationTrail = DocumentNavigationTrail::itemCreate($request, $document);

        return view('documents.items.create', compact(
            'document',
            'item',
            'products',
            'navigationTrail',
        ));
    }

    public function store(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $productSelector = app(ProductLineItemSelector::class);

        $data = $request->validate([
            'product_id' => $productSelector->nullableRulesFor(
                tenantId: (string) $document->tenant_id,
                enabled: true,
            ),
            ...app(LineItemValidationRules::class)->baseRules(),
        ]);

        $data['product_id'] = $productSelector->normalizeProductId(
            productId: $data['product_id'] ?? null,
            enabled: true,
        );

        $productSelector->assertViewable(
            user: auth()->user(),
            tenantId: (string) $document->tenant_id,
            productId: $data['product_id'],
            enabled: true,
        );

        $data = $this->syncDerivedFields($data);

        $data['tenant_id'] = $document->tenant_id;
        $data['document_id'] = $document->id;
        $data['status'] = DocumentItemCatalog::STATUS_PENDING;
        $data['line_total'] = $data['subtotal'];

        unset($data['subtotal']);

        DocumentItem::create($data);

        if ($document->status === DocumentCatalog::STATUS_DRAFT) {
            $document->update([
                'status' => DocumentCatalog::STATUS_PENDING_APPROVAL,
                'updated_by' => auth()->id(),
            ]);
        }

        app(DocumentTotalsCalculator::class)->recalculate($document->fresh());

        $navigationTrail = DocumentNavigationTrail::show($request, $document->fresh());

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem agregado correctamente.');
    }

    public function edit(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        try {
            app(DocumentsHooks::class)->beforeDocumentItemEdit($document, $item);
        } catch (InvalidArgumentException $exception) {
            $navigationTrail = DocumentNavigationTrail::show($request, $document);

            return redirect()
                ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
                ->withErrors([
                    'item' => $exception->getMessage(),
                ]);
        }

        $products = app(ProductLineItemSelector::class)->optionsFor(
            user: auth()->user(),
            tenantId: (string) $document->tenant_id,
            enabled: true,
        );

        $navigationTrail = DocumentNavigationTrail::itemEdit($request, $document, $item);

        return view('documents.items.edit', compact(
            'document',
            'item',
            'products',
            'navigationTrail',
        ));
    }

    public function update(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $productSelector = app(ProductLineItemSelector::class);

        $data = $request->validate([
            'product_id' => $productSelector->nullableRulesFor(
                tenantId: (string) $document->tenant_id,
                enabled: true,
            ),
            ...app(LineItemValidationRules::class)->baseRules(),
        ]);

        $data['product_id'] = $productSelector->normalizeProductId(
            productId: $data['product_id'] ?? null,
            enabled: true,
        );

        $productSelector->assertViewable(
            user: auth()->user(),
            tenantId: (string) $document->tenant_id,
            productId: $data['product_id'],
            enabled: true,
        );

        try {
            $data = app(DocumentsHooks::class)->beforeDocumentItemUpdate($document, $item, $data);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors([
                    'quantity' => $exception->getMessage(),
                ])
                ->withInput();
        }

        $data = $this->syncDerivedFields($data);
        $data['line_total'] = $data['subtotal'];

        unset($data['subtotal']);

        $item->update($data);

        app(DocumentsHooks::class)->afterDocumentItemUpdate($document, $item);

        app(DocumentTotalsCalculator::class)->recalculate($document->fresh());

        $navigationTrail = DocumentNavigationTrail::show($request, $document);

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        try {
            app(DocumentsHooks::class)->beforeDocumentItemDestroy($document, $item);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors([
                    'item' => $exception->getMessage(),
                ]);
        }

        $item->delete();

        app(DocumentTotalsCalculator::class)->recalculate($document->fresh());

        $navigationTrail = DocumentNavigationTrail::show($request, $document);

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem eliminado correctamente.');
    }

    protected function syncDerivedFields(array $data): array
    {
        $math = app(LineItemMath::class);

        $quantity = $math->normalizeQuantity($data['quantity'] ?? 0);
        $unitPrice = $math->normalizeMoney($data['unit_price'] ?? 0);

        $data['quantity'] = $quantity;
        $data['unit_price'] = $unitPrice;
        $data['subtotal'] = $math->lineTotal($quantity, $unitPrice);

        return $data;
    }
}
