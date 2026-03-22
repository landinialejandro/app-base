<?php

// FILE: app/Http/Controllers/DocumentItemController.php | V5

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Product;
use App\Support\Documents\DocumentTotalsCalculator;
use App\Support\Navigation\NavigationTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentItemController extends Controller
{
    public function create(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $products = Product::query()
            ->where('tenant_id', $document->tenant_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $item = new DocumentItem([
            'position' => ((int) $document->items()->max('position')) + 1,
            'quantity' => 1,
            'unit_price' => null,
        ]);

        $navigationTrail = NavigationTrail::fromRequest($request);

        if (empty($navigationTrail) || ! NavigationTrail::hasNode($navigationTrail, 'documents.show', $document->id)) {
            $navigationTrail = NavigationTrail::base([
                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                NavigationTrail::makeNode('documents.index', null, 'Documentos', route('documents.index')),
                NavigationTrail::makeNode(
                    'documents.show',
                    $document->id,
                    $document->number ?: 'Documento #'.$document->id,
                    route('documents.show', ['document' => $document])
                ),
            ]);

            $navigationTrail = NavigationTrail::replaceCurrentUrl(
                $navigationTrail,
                route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            );
        }

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'documents.items.create',
                $document->id,
                'Agregar ítem',
                route('documents.items.create', ['document' => $document])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('documents.items.create', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
        );

        return view('documents.items.create', compact('document', 'item', 'products', 'navigationTrail'));
    }

    public function store(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $navigationTrail = NavigationTrail::fromRequest($request);

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

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'documents.show',
                $document->id,
                $document->number ?: 'Documento #'.$document->id,
                route('documents.show', ['document' => $document])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
        );

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem agregado correctamente.');
    }

    public function edit(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $products = Product::query()
            ->where('tenant_id', $document->tenant_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $navigationTrail = NavigationTrail::fromRequest($request);

        if (empty($navigationTrail) || ! NavigationTrail::hasNode($navigationTrail, 'documents.show', $document->id)) {
            $navigationTrail = NavigationTrail::base([
                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                NavigationTrail::makeNode('documents.index', null, 'Documentos', route('documents.index')),
                NavigationTrail::makeNode(
                    'documents.show',
                    $document->id,
                    $document->number ?: 'Documento #'.$document->id,
                    route('documents.show', ['document' => $document])
                ),
            ]);

            $navigationTrail = NavigationTrail::replaceCurrentUrl(
                $navigationTrail,
                route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            );
        }

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'documents.items.edit',
                $item->id,
                'Editar ítem',
                route('documents.items.edit', ['document' => $document, 'item' => $item])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('documents.items.edit', ['document' => $document, 'item' => $item] + NavigationTrail::toQuery($navigationTrail))
        );

        return view('documents.items.edit', compact('document', 'item', 'products', 'navigationTrail'));
    }

    public function update(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $navigationTrail = NavigationTrail::fromRequest($request);

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

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'documents.show',
                $document->id,
                $document->number ?: 'Documento #'.$document->id,
                route('documents.show', ['document' => $document])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
        );

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(Request $request, Document $document, DocumentItem $item)
    {
        $this->authorize('update', $document);

        abort_unless((int) $item->document_id === (int) $document->id, 404);

        $navigationTrail = NavigationTrail::fromRequest($request);

        $item->delete();

        DocumentTotalsCalculator::apply($document->fresh());

        $navigationTrail = NavigationTrail::appendOrCollapse(
            $navigationTrail,
            NavigationTrail::makeNode(
                'documents.show',
                $document->id,
                $document->number ?: 'Documento #'.$document->id,
                route('documents.show', ['document' => $document])
            )
        );

        $navigationTrail = NavigationTrail::replaceCurrentUrl(
            $navigationTrail,
            route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
        );

        return redirect()
            ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Ítem eliminado correctamente.');
    }
}
