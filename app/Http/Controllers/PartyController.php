<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Models\Party;

class PartyController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

        $parties = Party::query()
            ->latest()
            ->get();

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

        return view('parties.show', [
            'tenant' => $tenant,
            'party' => $party,
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
            ->with('success', "Contacto actualizado correctamente.");
    }

    public function destroy(Party $party)
    {
        $party->delete();

        return redirect()
            ->route('parties.index')
            ->with('success', "Contacto eliminado correctamente.");
    }
}