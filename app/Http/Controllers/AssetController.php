<?php

// FILE: app/Http/Controllers/AssetController.php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Support\Catalogs\AssetCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(): View
    {
        $assets = Asset::query()
            ->with('party')
            ->orderBy('name')
            ->paginate(25);

        return view('assets.index', compact('assets'));
    }

    public function create(): View
    {
        $parties = Party::query()
            ->orderBy('name')
            ->get();

        return view('assets.create', compact('parties'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = validator($request->all(), $this->rules())->validate();

        $asset = new Asset($validated);
        $asset->save();

        return redirect()
            ->route('assets.index')
            ->with('success', 'Activo creado correctamente.');
    }

    public function show(Asset $asset): View
    {
        $asset->load('party');

        $orders = Order::query()
            ->with('party')
            ->where('asset_id', $asset->id)
            ->latest()
            ->get();

        return view('assets.show', compact('asset', 'orders'));
    }

    public function edit(Asset $asset): View
    {
        $parties = Party::query()
            ->orderBy('name')
            ->get();

        return view('assets.edit', compact('asset', 'parties'));
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $validated = validator($request->all(), $this->rules())->validate();

        $asset->update($validated);

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', 'Activo actualizado correctamente.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
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
