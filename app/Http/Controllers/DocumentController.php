<?php

// FILE: app/Http/Controllers/DocumentController.php | V16

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Order;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Catalogs\DocumentCatalog;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Documents\DocumentTotalsCalculator;
use App\Support\Navigation\DocumentNavigationTrail;
use App\Support\Navigation\NavigationTrail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $security = app(Security::class);
        $user = auth()->user();

        $q = trim((string) $request->get('q', ''));
        $partyId = $request->get('party_id');
        $assetId = $request->get('asset_id');
        $orderId = $request->get('order_id');
        $kind = $request->get('kind');
        $status = $request->get('status');
        $issuedAt = $request->get('issued_at');

        $parties = $security
            ->scope($user, 'parties.viewAny', Party::query())
            ->orderBy('name')
            ->get();

        $assets = $security
            ->scope($user, 'assets.viewAny', Asset::query())
            ->with('party')
            ->orderBy('name')
            ->get();

        $orders = $security
            ->scope($user, 'orders.viewAny', Order::query())
            ->with(['party', 'asset'])
            ->latest()
            ->get();

        $documents = $security
            ->scope($user, 'documents.viewAny', Document::query())
            ->with(['party', 'order', 'asset', 'items'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('number', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($partyId, fn ($query) => $query->where('party_id', $partyId))
            ->when($assetId, fn ($query) => $query->where('asset_id', $assetId))
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->when($kind, fn ($query) => $query->where('kind', $kind))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($issuedAt, fn ($query) => $query->whereDate('issued_at', $issuedAt))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('documents.index', compact('documents', 'parties', 'assets', 'orders'));
    }

public function create(Request $request)
{
    $tenant = app('tenant');
    $security = app(Security::class);
    $user = auth()->user();

    $parties = $security
        ->scope($user, 'parties.viewAny', Party::query())
        ->orderBy('name')
        ->get();

    $orders = $security
        ->scope($user, 'orders.viewAny', Order::query())
        ->with(['party', 'asset'])
        ->latest()
        ->get();

    $assets = $security
        ->scope($user, 'assets.viewAny', Asset::query())
        ->with('party')
        ->orderBy('name')
        ->get();

    $order = null;
    $asset = null;

    if ($request->filled('order_id')) {
        $order = $security
            ->scope($user, 'orders.viewAny', Order::query())
            ->with(['party', 'asset'])
            ->where('id', $request->integer('order_id'))
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();
    }

    if ($request->filled('asset_id')) {
        $asset = $security
            ->scope($user, 'assets.viewAny', Asset::query())
            ->with('party')
            ->where('id', $request->integer('asset_id'))
            ->where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->first();
    }

    $requestedGroup = (string) $request->get('group', '');
    $prefilledGroup = in_array($requestedGroup, DocumentCatalog::groups(), true)
        ? $requestedGroup
        : DocumentCatalog::GROUP_SALE;

    $requestedKind = (string) $request->get('kind', '');
    $prefilledKind = in_array($requestedKind, DocumentCatalog::kinds(), true)
        ? $requestedKind
        : DocumentCatalog::KIND_QUOTE;

    $security->authorize(
        $user,
        'documents.create',
        Document::class,
        ['kind' => $prefilledKind]
    );

    $prefilledPartyId = $order?->party_id;
    $prefilledAssetId = $order?->asset_id;

    if (! $order && $asset) {
        $prefilledAssetId = $asset->id;
        $prefilledPartyId = $asset->party_id;
    }

    if (! $order && ! $asset && $request->filled('party_id')) {
        $party = $security
            ->scope($user, 'parties.viewAny', Party::query())
            ->where('id', $request->integer('party_id'))
            ->where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->first();

        if ($party) {
            $prefilledPartyId = $party->id;
        }
    }

    $document = new Document([
        'party_id' => $prefilledPartyId,
        'order_id' => $order?->id,
        'asset_id' => $prefilledAssetId,
        'group' => $prefilledGroup,
        'kind' => $prefilledKind,
        'status' => DocumentCatalog::STATUS_DRAFT,
        'issued_at' => now(),
    ]);

    $navigationTrail = DocumentNavigationTrail::create($request, $order);

    return view('documents.create', compact(
        'document',
        'order',
        'parties',
        'orders',
        'assets',
        'navigationTrail',
    ));
}

public function store(Request $request)
{
    $tenant = app('tenant');
    $security = app(Security::class);
    $user = auth()->user();

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
        'group' => [
            'required',
            Rule::in(DocumentCatalog::groups()),
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

    if (! DocumentCatalog::isValidKindForGroup($data['group'], $data['kind'])) {
        return back()
            ->withErrors([
                'kind' => 'El tipo seleccionado no corresponde al grupo del documento.',
            ])
            ->withInput();
    }

    $security->authorize(
        $user,
        'documents.create',
        Document::class,
        ['kind' => $data['kind']]
    );

    $issuedAt = $this->resolveIssuedAt($data['issued_at'] ?? null);
    $order = null;

    if (! empty($data['order_id'])) {
        $order = $security
            ->scope($user, 'orders.viewAny', Order::query())
            ->where('id', $data['order_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $data['party_id'] = $order->party_id;
        $data['asset_id'] = $order->asset_id;
    }

    if (! empty($data['asset_id'])) {
        $asset = $security
            ->scope($user, 'assets.viewAny', Asset::query())
            ->whereKey($data['asset_id'])
            ->firstOrFail();

        if ((int) $asset->party_id !== (int) $data['party_id']) {
            return back()
                ->withErrors([
                    'asset_id' => 'El activo seleccionado pertenece a otro contacto.',
                ])
                ->withInput();
        }
    }

    if (! empty($data['party_id'])) {
        $security
            ->scope($user, 'parties.viewAny', Party::query())
            ->whereKey($data['party_id'])
            ->firstOrFail();
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

    $navigationTrail = DocumentNavigationTrail::show($request, $document);

    return redirect()
        ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
        ->with('success', "Documento creado correctamente con número {$document->number}.");
}

public function storeFromOrder(Request $request, Order $order)
{
    $security = app(Security::class);
    $user = auth()->user();

    $order = $security
        ->scope($user, 'orders.viewAny', Order::query())
        ->whereKey($order->id)
        ->firstOrFail();

    $defaultGroup = match ($order->kind) {
        'purchase' => DocumentCatalog::GROUP_PURCHASE,
        'service' => DocumentCatalog::GROUP_SERVICE,
        default => DocumentCatalog::GROUP_SALE,
    };

    $data = $request->validate([
        'group' => [
            'nullable',
            Rule::in(DocumentCatalog::groups()),
        ],
        'kind' => [
            'required',
            Rule::in(DocumentCatalog::kinds()),
        ],
    ]);

    $data['group'] = $data['group'] ?? $defaultGroup;

    if (! DocumentCatalog::isValidKindForGroup($data['group'], $data['kind'])) {
        return back()
            ->withErrors([
                'kind' => 'El tipo seleccionado no corresponde al grupo del documento.',
            ]);
    }

    $security->authorize(
        $user,
        'documents.create',
        Document::class,
        ['kind' => $data['kind']]
    );

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
            'group' => $data['group'],
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

    $navigationTrail = DocumentNavigationTrail::show($request, $document);

    return redirect()
        ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
        ->with('success', $message);
}

    public function show(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        $document->load([
            'party',
            'order',
            'asset',
            'creator',
            'updater',
            'items.product',
            'attachments' => fn ($query) => $query->ordered(),
        ]);

        $navigationTrail = DocumentNavigationTrail::show($request, $document);

        return view('documents.show', compact('document', 'navigationTrail'));
    }

    public function edit(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $security = app(Security::class);
        $user = auth()->user();

        $parties = $security
            ->scope($user, 'parties.viewAny', Party::query())
            ->orderBy('name')
            ->get();

        $orders = $security
            ->scope($user, 'orders.viewAny', Order::query())
            ->with(['party', 'asset'])
            ->latest()
            ->get();

        $assets = $security
            ->scope($user, 'assets.viewAny', Asset::query())
            ->with('party')
            ->orderBy('name')
            ->get();

        $navigationTrail = DocumentNavigationTrail::edit($request, $document);

        return view('documents.edit', compact('document', 'parties', 'orders', 'assets', 'navigationTrail'));
    }

public function update(Request $request, Document $document)
{
    $this->authorize('update', $document);

    $tenant = app('tenant');
    $security = app(Security::class);
    $user = auth()->user();

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
        'group' => [
            'required',
            Rule::in(DocumentCatalog::groups()),
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

    if (! DocumentCatalog::isValidKindForGroup($data['group'], $data['kind'])) {
        return back()
            ->withErrors([
                'kind' => 'El tipo seleccionado no corresponde al grupo del documento.',
            ])
            ->withInput();
    }

    if ($document->number && $data['kind'] !== $document->kind) {
        return back()
            ->withErrors([
                'kind' => 'No se puede cambiar el tipo de un documento que ya fue numerado.',
            ])
            ->withInput();
    }

    if ($document->number && $data['group'] !== $document->group) {
        return back()
            ->withErrors([
                'group' => 'No se puede cambiar el grupo de un documento que ya fue numerado.',
            ])
            ->withInput();
    }

    $currentOrderId = $document->order_id !== null ? (int) $document->order_id : null;
    $incomingOrderId = ! empty($data['order_id']) ? (int) $data['order_id'] : null;

    if ($currentOrderId !== null && $incomingOrderId === null) {
        return back()
            ->withErrors([
                'order_id' => 'No se puede quitar la orden asociada de un documento ya vinculado.',
            ])
            ->withInput();
    }

    if ($currentOrderId !== null && $incomingOrderId !== null && $currentOrderId !== $incomingOrderId) {
        return back()
            ->withErrors([
                'order_id' => 'No se puede cambiar la orden asociada de un documento ya vinculado.',
            ])
            ->withInput();
    }

    $issuedAt = $this->resolveIssuedAt(
        $data['issued_at'] ?? ($document->issued_at?->toDateString() ?? null)
    );

    $order = null;

    if ($incomingOrderId !== null) {
        $order = $security
            ->scope($user, 'orders.viewAny', Order::query())
            ->where('id', $incomingOrderId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $data['party_id'] = $order->party_id;
        $data['asset_id'] = $order->asset_id;
    }

    $security->authorize(
        $user,
        'documents.update',
        $document,
        ['kind' => $data['kind']]
    );

    if (! empty($data['asset_id'])) {
        $asset = $security
            ->scope($user, 'assets.viewAny', Asset::query())
            ->whereKey($data['asset_id'])
            ->firstOrFail();

        if ((int) $asset->party_id !== (int) $data['party_id']) {
            return back()
                ->withErrors([
                    'asset_id' => 'El activo seleccionado pertenece a otro contacto.',
                ])
                ->withInput();
        }
    }

    if (! empty($data['party_id'])) {
        $security
            ->scope($user, 'parties.viewAny', Party::query())
            ->whereKey($data['party_id'])
            ->firstOrFail();
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

    $navigationTrail = DocumentNavigationTrail::show($request, $document);

    return redirect()
        ->route('documents.show', ['document' => $document] + NavigationTrail::toQuery($navigationTrail))
        ->with('success', 'Documento actualizado.');
}

    public function destroy(Request $request, Document $document)
    {
        $this->authorize('delete', $document);

        $navigationTrail = DocumentNavigationTrail::show($request, $document);
        $redirectUrl = NavigationTrail::previousUrl($navigationTrail, route('documents.index'));

        $document->delete();

        return redirect()
            ->to($redirectUrl)
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

    public function print(Document $document)
    {
        $this->authorize('view', $document);

        $document->load([
            'party',
            'order',
            'asset',
            'items.product',
        ]);

        return view('documents.print', [
            'document' => $document,
            'renderMode' => 'print',
        ]);
    }

    public function pdf(Document $document)
    {
        $this->authorize('view', $document);

        $document->load([
            'party',
            'order',
            'asset',
            'items.product',
        ]);

        $filename = $document->number
            ? 'documento-'.strtolower(str_replace([' ', '/'], '-', $document->number)).'.pdf'
            : 'documento-'.$document->id.'.pdf';

        $pdf = Pdf::loadView('documents.print', [
            'document' => $document,
            'renderMode' => 'pdf',
        ]);

        return $pdf->download($filename);
    }
}
