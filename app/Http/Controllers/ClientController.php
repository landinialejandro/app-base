<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

        $clients = Client::query()
            ->latest()
            ->get();

        return view('clients.index', [
            'tenant' => $tenant,
            'clients' => $clients,
        ]);
    }

    public function create()
    {
        $tenant = app('tenant');

        return view('clients.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $client = Client::create($data);

        return redirect()
            ->route('clients.index')
            ->with('success', "Cliente #{$client->id} creado correctamente.");
    }

    public function show(Client $client)
    {
        $tenant = app('tenant');

        return view('clients.show', [
            'tenant' => $tenant,
            'client' => $client,
        ]);
    }

    public function edit(Client $client)
    {
        $tenant = app('tenant');

        return view('clients.edit', [
            'tenant' => $tenant,
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->update($data);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}