<?php

// FILE: app/Http/Controllers/DocumentController.php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Order;
use App\Models\Party;
use App\Support\Catalogs\DocumentCatalog;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Documents\DocumentTotalsCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::with(['party', 'order', 'asset', 'items'])
            ->latest()
            ->paginate(20);

        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        $parties = Party::orderBy('name')->get();
        $orders = Order::with(['party', 'asset'])->latest()->get();
        $assets = Asset::with('party')->orderBy('name')->get();

        $document = new Document([
            'kind' => DocumentCatalog::KIND_QUOTE,
            'status' => DocumentCatalog::STATUS_DRAFT,
            'issued_at' => now(),
        ]);

        return view('documents.create', compact('document', 'parties', 'orders', 'assets'));
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

            'order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id);
                }),
            ],

            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],

            'kind' => [
                'required',
                Rule::in(DocumentCatalog::kinds()),
            ],

            'status' => [
                'required',
                Rule::in(DocumentCatalog::statuses()),
            ],

            'issued_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $issuedAt = $this->resolveIssuedAt($data['issued_at'] ?? null);
        $order = null;

        if (! empty($data['order_id'])) {
            $order = Order::query()->find($data['order_id']);

            if ($order) {
                $data['party_id'] = $order->party_id;
                $data['asset_id'] = $order->asset_id;
            }
        }

        if (! empty($data['asset_id'])) {
            $asset = Asset::query()->findOrFail($data['asset_id']);

            if ((int) $asset->party_id !== (int) $data['party_id']) {
                return back()
                    ->withErrors([
                        'asset_id' => 'El activo seleccionado pertenece a otro contacto.',
                    ])
                    ->withInput();
            }
        }

        $issuedAtError = $this->validateIssuedAtForDocument(
            issuedAt: $issuedAt,
            kind: $data['kind'],
            order: $order,
        );

        if ($issuedAtError) {
            return back()
                ->withErrors(['issued_at' => $issuedAtError])
                ->withInput();
        }

        $data['issued_at'] = $issuedAt->toDateString();

        $document = DB::transaction(function () use ($tenant, $data) {
            $sequence = DocumentNumberGenerator::generate(
                tenantId: $tenant->id,
                kind: $data['kind'],
                pointOfSale: '0001',
            );

            $payload = array_merge($data, [
                'number' => $sequence['number'],
                'sequence_prefix' => $sequence['prefix'],
                'point_of_sale' => $sequence['point_of_sale'],
                'sequence_number' => $sequence['sequence_number'],
                'created_by' => auth()->id(),
            ]);

            return Document::create($payload);
        });

        return redirect()
            ->route('documents.show', $document)
            ->with('success', "Documento creado correctamente con número {$document->number}.");
    }

    public function storeFromOrder(Request $request, Order $order)
    {
        $data = $request->validate([
            'kind' => [
                'required',
                Rule::in([
                    DocumentCatalog::KIND_QUOTE,
                    DocumentCatalog::KIND_DELIVERY_NOTE,
                    DocumentCatalog::KIND_INVOICE,
                    DocumentCatalog::KIND_WORK_ORDER,
                ]),
            ],
        ]);

        abort_unless($order->party_id, 422, 'La orden debe tener un contacto asociado para generar documentos.');

        $existingDocumentsCount = $order->documents()->count();
        $existingSameKindCount = $order->documents()
            ->where('kind', $data['kind'])
            ->count();

        $issuedAt = now()->startOfDay();

        $issuedAtError = $this->validateIssuedAtForDocument(
            issuedAt: $issuedAt,
            kind: $data['kind'],
            order: $order,
        );

        if ($issuedAtError) {
            return back()
                ->withErrors(['issued_at' => $issuedAtError]);
        }

        $document = DB::transaction(function () use ($order, $data, $issuedAt) {
            $sequence = DocumentNumberGenerator::generate(
                tenantId: $order->tenant_id,
                kind: $data['kind'],
                pointOfSale: '0001',
            );

            $document = Document::create([
                'tenant_id' => $order->tenant_id,
                'party_id' => $order->party_id,
                'order_id' => $order->id,
                'asset_id' => $order->asset_id,
                'kind' => $data['kind'],
                'number' => $sequence['number'],
                'sequence_prefix' => $sequence['prefix'],
                'point_of_sale' => $sequence['point_of_sale'],
                'sequence_number' => $sequence['sequence_number'],
                'status' => DocumentCatalog::STATUS_DRAFT,
                'issued_at' => $issuedAt->toDateString(),
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
                'created_by' => auth()->id(),
            ]);

            $this->copyItemsFromOrder($order, $document);

            DocumentTotalsCalculator::apply($document);

            return $document;
        });

        $kindLabel = DocumentCatalog::label($data['kind']);

        $message = "Documento {$kindLabel} creado correctamente desde la orden con número {$document->number}.";

        if ($existingDocumentsCount > 0) {
            $message .= " Esta orden ya tenía {$existingDocumentsCount} documento(s) asociado(s).";
        }

        if ($existingSameKindCount > 0) {
            $message .= " Ya existía(n) {$existingSameKindCount} documento(s) del tipo {$kindLabel}.";
        }

        return redirect()
            ->route('documents.show', $document)
            ->with('success', $message);
    }

    public function show(Document $document)
    {
        $document->load([
            'party',
            'order',
            'asset',
            'creator',
            'updater',
            'items.product',
        ]);

        return view('documents.show', compact('document'));
    }

    public function edit(Document $document)
    {
        $parties = Party::orderBy('name')->get();
        $orders = Order::with(['party', 'asset'])->latest()->get();
        $assets = Asset::with('party')->orderBy('name')->get();

        return view('documents.edit', compact('document', 'parties', 'orders', 'assets'));
    }

    public function update(Request $request, Document $document)
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

            'order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id);
                }),
            ],

            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->whereNull('deleted_at');
                }),
            ],

            'kind' => [
                'required',
                Rule::in(DocumentCatalog::kinds()),
            ],

            'status' => [
                'required',
                Rule::in(DocumentCatalog::statuses()),
            ],

            'issued_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($document->number && $data['kind'] !== $document->kind) {
            return back()
                ->withErrors([
                    'kind' => 'No se puede cambiar el tipo de un documento que ya fue numerado.',
                ])
                ->withInput();
        }

        $issuedAt = $this->resolveIssuedAt(
            $data['issued_at'] ?? ($document->issued_at?->toDateString() ?? null)
        );

        $order = null;

        if (! empty($data['order_id'])) {
            $order = Order::query()->find($data['order_id']);

            if ($order) {
                $data['party_id'] = $order->party_id;
                $data['asset_id'] = $order->asset_id;
            }
        }

        if (! empty($data['asset_id'])) {
            $asset = Asset::query()->findOrFail($data['asset_id']);

            if ((int) $asset->party_id !== (int) $data['party_id']) {
                return back()
                    ->withErrors([
                        'asset_id' => 'El activo seleccionado pertenece a otro contacto.',
                    ])
                    ->withInput();
            }
        }

        $issuedAtError = $this->validateIssuedAtForDocument(
            issuedAt: $issuedAt,
            kind: $data['kind'],
            order: $order,
        );

        if ($issuedAtError) {
            return back()
                ->withErrors(['issued_at' => $issuedAtError])
                ->withInput();
        }

        $data['issued_at'] = $issuedAt->toDateString();
        $data['updated_by'] = auth()->id();

        $document->update($data);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Documento actualizado.');
    }

    public function destroy(Document $document)
    {
        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Documento eliminado.');
    }

    protected function copyItemsFromOrder(Order $order, Document $document): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $orderItem) {
            $quantity = (float) $orderItem->quantity;
            $unitPrice = (float) $orderItem->unit_price;
            $lineTotal = $quantity * $unitPrice;

            DocumentItem::create([
                'tenant_id' => $document->tenant_id,
                'document_id' => $document->id,
                'product_id' => $orderItem->product_id,
                'position' => $orderItem->position,
                'kind' => $orderItem->kind,
                'description' => $orderItem->description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        }
    }

    protected function resolveIssuedAt(?string $issuedAt): Carbon
    {
        return $issuedAt
            ? Carbon::parse($issuedAt)->startOfDay()
            : now()->startOfDay();
    }

    protected function validateIssuedAtForDocument(Carbon $issuedAt, string $kind, ?Order $order = null): ?string
    {
        $today = now()->startOfDay();

        if ($kind === DocumentCatalog::KIND_INVOICE && $issuedAt->gt($today)) {
            return 'La fecha de una factura no puede ser futura.';
        }

        if ($kind !== DocumentCatalog::KIND_INVOICE) {
            $maxFutureDate = $today->copy()->addDays(30);

            if ($issuedAt->gt($maxFutureDate)) {
                return 'La fecha del documento no puede superar los 30 días hacia el futuro.';
            }
        }

        if ($order && $order->ordered_at) {
            $orderDate = Carbon::parse($order->ordered_at)->startOfDay();

            if ($issuedAt->lt($orderDate)) {
                return 'La fecha del documento no puede ser anterior a la fecha de la orden asociada.';
            }
        }

        return null;
    }
}
