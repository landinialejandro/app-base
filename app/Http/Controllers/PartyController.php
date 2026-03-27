<?php

// FILE: app/Http/Controllers/PartyController.php | V4

namespace App\Http\Controllers;

use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\PartyNavigationTrail;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Party::class);

        $tenant = app('tenant');

        $q = trim((string) $request->get('q', ''));
        $kind = $request->get('kind');
        $isActive = $request->get('is_active');

        $parties = Party::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('name', 'like', "%{$q}%")
                        ->orWhere('display_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('document_number', 'like', "%{$q}%")
                        ->orWhere('tax_id', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($kind, function ($query) use ($kind) {
                $query->where('kind', $kind);
            })
            ->when($isActive !== null && $isActive !== '', function ($query) use ($isActive) {
                $query->where('is_active', (bool) $isActive);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('parties.index', [
            'tenant' => $tenant,
            'parties' => $parties,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Party::class);

        $tenant = app('tenant');
        $navigationTrail = PartyNavigationTrail::create($request);

        return view('parties.create', [
            'tenant' => $tenant,
            'navigationTrail' => $navigationTrail,
        ]);
    }

    public function store(StorePartyRequest $request)
    {
        $this->authorize('create', Party::class);

        $data = $request->validated();

        $party = Party::create($data);
        $navigationTrail = PartyNavigationTrail::show($request, $party);

        return redirect()
            ->route('parties.show', ['party' => $party] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', "Contacto #{$party->id} creado correctamente.");
    }

    public function show(Request $request, Party $party)
    {
        $this->authorize('view', $party);

        $tenant = app('tenant');

        $assets = Asset::query()
            ->where('party_id', $party->id)
            ->orderBy('name')
            ->get();

        $orders = Order::query()
            ->with('asset')
            ->where('party_id', $party->id)
            ->latest()
            ->get();

        $documents = Document::query()
            ->with(['order', 'asset'])
            ->where('party_id', $party->id)
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();

        $navigationTrail = PartyNavigationTrail::show($request, $party);

        return view('parties.show', [
            'tenant' => $tenant,
            'party' => $party,
            'assets' => $assets,
            'orders' => $orders,
            'documents' => $documents,
            'navigationTrail' => $navigationTrail,
        ]);
    }

    public function edit(Request $request, Party $party)
    {
        $this->authorize('update', $party);

        $tenant = app('tenant');
        $navigationTrail = PartyNavigationTrail::edit($request, $party);

        return view('parties.edit', [
            'tenant' => $tenant,
            'party' => $party,
            'navigationTrail' => $navigationTrail,
        ]);
    }

    public function update(UpdatePartyRequest $request, Party $party)
    {
        $this->authorize('update', $party);

        $data = $request->validated();

        $party->update($data);
        $navigationTrail = PartyNavigationTrail::show($request, $party);

        return redirect()
            ->route('parties.show', ['party' => $party] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Contacto actualizado correctamente.');
    }

    public function destroy(Request $request, Party $party)
    {
        $this->authorize('delete', $party);

        $navigationTrail = PartyNavigationTrail::show($request, $party);
        $redirectUrl = NavigationTrail::previousUrl($navigationTrail, route('parties.index'));

        $party->delete();

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Contacto eliminado correctamente.');
    }
}
