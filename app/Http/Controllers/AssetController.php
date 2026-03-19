<?php

// FILE: app/Http/Controllers/AssetController.php | V2

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Support\Catalogs\AssetCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Asset::class);

        $q = trim((string) $request->get('q', ''));
        $partyId = $request->get('party_id');
        $kind = $request->get('kind');
        $status = $request->get('status');

        $parties = Party::query()
            ->orderBy('name')
            ->get();

        $assets = Asset::query()
            ->with('party')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('name', 'like', "%{$q}%")
                        ->orWhere('internal_code', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($partyId, function ($query) use ($partyId) {
                $query->where('party_id', $partyId);
            })
            ->when($kind, function ($query) use ($kind) {
                $query->where('kind', $kind);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('assets.index', compact('assets', 'parties'));
    }

    public function create(): View
    {
        $this->authorize('create', Asset::class);

        $parties = Party::query()
            ->orderBy('name')
            ->get();

        return view('assets.create', compact('parties'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Asset::class);

        $validated = validator($request->all(), $this->rules())->validate();

        $asset = new Asset($validated);
        $asset->save();

        return redirect()
            ->route('assets.index')
            ->with('success', 'Activo creado correctamente.');
    }

    public function show(Asset $asset): View
    {
        $this->authorize('view', $asset);

        $asset->load('party');

        $orders = Order::query()
            ->with('party')
            ->where('asset_id', $asset->id)
            ->latest()
            ->get();

        $documents = Document::query()
            ->with(['party', 'order'])
            ->where('asset_id', $asset->id)
            ->latest()
            ->get();

        return view('assets.show', compact('asset', 'orders', 'documents'));
    }

    public function edit(Asset $asset): View
    {
        $this->authorize('update', $asset);

        $parties = Party::query()
            ->orderBy('name')
            ->get();

        return view('assets.edit', compact('asset', 'parties'));
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $validated = validator($request->all(), $this->rules())->validate();

        $asset->update($validated);

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', 'Activo actualizado correctamente.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->authorize('delete', $asset);

        $asset->delete();

        return redirect()
            ->route('assets.index')
            ->with('success', 'Activo eliminado correctamente.');
    }

    private function rules(): array
    {
        $tenantId = app('tenant')->id;

        return [
            'party_id' => [
                'required',
                'integer',
                Rule::exists('parties', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'kind' => [
                'required',
                'string',
                Rule::in(AssetCatalog::kinds()),
            ],
            'relationship_type' => [
                'required',
                'string',
                Rule::in(AssetCatalog::relationshipTypes()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'internal_code' => ['nullable', 'string', 'max:100'],
            'status' => [
                'required',
                'string',
                Rule::in(AssetCatalog::statuses()),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
