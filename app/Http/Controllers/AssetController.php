<?php

// FILE: app/Http/Controllers/AssetController.php | V9

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\AssetCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Navigation\AssetNavigationTrail;
use App\Support\Navigation\NavigationTrail;
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

        $parties = app(Security::class)
            ->scope(auth()->user(), 'parties.viewAny', Party::query())
            ->orderBy('name')
            ->get();

        $assets = app(Security::class)
            ->scope(auth()->user(), 'assets.viewAny', Asset::query())
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

    public function create(Request $request): View
    {
        $this->authorize('create', Asset::class);

        $parties = app(Security::class)
            ->scope(auth()->user(), 'parties.viewAny', Party::query())
            ->orderBy('name')
            ->get();

        $prefilledParty = null;

        if ($request->filled('party_id')) {
            $prefilledParty = app(Security::class)
                ->scope(auth()->user(), 'parties.viewAny', Party::query())
                ->whereKey($request->integer('party_id'))
                ->firstOrFail();
        }

        $navigationTrail = AssetNavigationTrail::create($request, $prefilledParty);

        return view('assets.create', compact('parties', 'prefilledParty', 'navigationTrail'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Asset::class);

        $validated = validator($request->all(), $this->rules())->validate();

        $asset = new Asset($validated);
        $asset->save();
        $asset->load('party');

        $navigationTrail = AssetNavigationTrail::show($request, $asset);

        return redirect()
            ->route('assets.show', ['asset' => $asset] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Activo creado correctamente.');
    }

    public function show(Request $request, Asset $asset): View
    {
        $this->authorize('view', $asset);

        $tenant = app('tenant');
        $security = app(Security::class);
        $user = auth()->user();

        $supportsAppointmentsModule = TenantModuleAccess::isEnabled(ModuleCatalog::APPOINTMENTS, $tenant);
        $supportsOrdersModule = TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant);
        $supportsDocumentsModule = TenantModuleAccess::isEnabled(ModuleCatalog::DOCUMENTS, $tenant);

        $asset->load([
            'party',
            'attachments' => fn ($query) => $query->ordered(),
        ]);

        $orders = $supportsOrdersModule
            ? $security
                ->scope($user, 'orders.viewAny', Order::query())
                ->with('party')
                ->where('asset_id', $asset->id)
                ->latest()
                ->get()
            : collect();

        $documents = $supportsDocumentsModule
            ? $security
                ->scope($user, 'documents.viewAny', Document::query())
                ->with(['party', 'order'])
                ->where('asset_id', $asset->id)
                ->latest()
                ->get()
            : collect();

        $navigationTrail = AssetNavigationTrail::show($request, $asset);

        return view('assets.show', compact(
            'asset',
            'orders',
            'documents',
            'navigationTrail',
            'supportsAppointmentsModule',
        ));
    }

    public function edit(Request $request, Asset $asset): View
    {
        $this->authorize('update', $asset);

        $parties = app(Security::class)
            ->scope(auth()->user(), 'parties.viewAny', Party::query())
            ->orderBy('name')
            ->get();

        $navigationTrail = AssetNavigationTrail::edit($request, $asset);

        return view('assets.edit', compact('asset', 'parties', 'navigationTrail'));
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $validated = validator($request->all(), $this->rules())->validate();

        $asset->update($validated);
        $asset->load('party');

        $navigationTrail = AssetNavigationTrail::show($request, $asset);

        return redirect()
            ->route('assets.show', ['asset' => $asset] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Activo actualizado correctamente.');
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('delete', $asset);

        $navigationTrail = AssetNavigationTrail::show($request, $asset);
        $redirectUrl = NavigationTrail::previousUrl($navigationTrail, route('assets.index'));

        $asset->delete();

        return redirect()
            ->to($redirectUrl)
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
