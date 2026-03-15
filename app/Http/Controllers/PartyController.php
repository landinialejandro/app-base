<?php

// FILE: app/Http/Controllers/PartyController.php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Models\Asset;
use App\Models\Party;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index(Request $request)
    {
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
            ->paginate(25)
            ->withQueryString();

        return view('parties.index', [
            'tenant' => $tenant,
            'parties' => $parties,
        ]);
    }

    public function create()
    {
        $tenant = app('tenant');

        return view('parties.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(StorePartyRequest $request)
    {
        $data = $request->validated();

        $party = Party::create($data);

        return redirect()
            ->route('parties.index')
            ->with('success', "Contacto #{$party->id} creado correctamente.");
    }

    public function show(Party $party)
    {
        $tenant = app('tenant');

        $assets = Asset::query()
            ->where('party_id', $party->id)
            ->orderBy('name')
            ->get();

        return view('parties.show', [
            'tenant' => $tenant,
            'party' => $party,
            'assets' => $assets,
        ]);
    }

    public function edit(Party $party)
    {
        $tenant = app('tenant');

        return view('parties.edit', [
            'tenant' => $tenant,
            'party' => $party,
        ]);
    }

    public function update(UpdatePartyRequest $request, Party $party)
    {
        $data = $request->validated();

        $party->update($data);

        return redirect()
            ->route('parties.show', $party)
            ->with('success', 'Contacto actualizado correctamente.');
    }

    public function destroy(Party $party)
    {
        $party->delete();

        return redirect()
            ->route('parties.index')
            ->with('success', 'Contacto eliminado correctamente.');
    }
}
