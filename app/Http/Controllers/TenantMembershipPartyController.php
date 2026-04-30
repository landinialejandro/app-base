<?php

// FILE: app/Http/Controllers/TenantMembershipPartyController.php | V2

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PartyCatalog;
use Illuminate\Http\Request;

class TenantMembershipPartyController extends Controller
{
    public function current(Request $request)
    {
        $tenant = app('tenant');
        $user = $request->user();

        $membership = $user->memberships()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->firstOrFail();

        return $this->openOrConfirm($request, $membership);
    }

    public function show(Request $request, Membership $membership)
    {
        $tenant = app('tenant');

        abort_unless($membership->tenant_id === $tenant->id, 403);

        $this->authorize('resolveParty', $membership);

        return $this->openOrConfirm($request, $membership);
    }

    public function confirm(Request $request, Membership $membership)
    {
        $tenant = app('tenant');

        abort_unless($membership->tenant_id === $tenant->id, 403);

        $this->authorize('resolveParty', $membership);

        $membership->loadMissing('user', 'party');

        if ($membership->party) {
            return redirect()
                ->route('parties.show', $membership->party)
                ->with('success', 'Ficha ampliada disponible.');
        }

        $matchingParty = $this->findMatchingParty($membership);

        return view('tenants.memberships.party-confirm', [
            'membership' => $membership,
            'matchingParty' => $matchingParty,
            'suggestedKind' => PartyCatalog::KIND_EMPLOYEE,
        ]);
    }

    public function store(Request $request, Membership $membership)
    {
        $tenant = app('tenant');

        abort_unless($membership->tenant_id === $tenant->id, 403);

        $this->authorize('resolveParty', $membership);

        $membership->loadMissing('user', 'party');

        if ($membership->party) {
            return redirect()
                ->route('parties.show', $party)
                ->with('success', 'Ficha ampliada disponible para edición.');
        }

        $data = $request->validate([
            'mode' => ['required', 'string', 'in:link_existing,create_new'],
        ]);

        if ($data['mode'] === 'link_existing') {
            $party = $this->findMatchingParty($membership);

            abort_unless($party, 422);
        } else {
            $this->authorizeCreateEmployeeParty($request);

            $party = Party::create([
                'tenant_id' => $membership->tenant_id,
                'kind' => PartyCatalog::KIND_EMPLOYEE,
                'name' => $membership->user?->name ?? 'Colaborador',
                'display_name' => $membership->user?->name,
                'email' => $this->normalizedUserEmail($membership) ?: null,
                'is_active' => true,
            ]);
        }

        $membership->forceFill([
            'party_id' => $party->id,
        ])->save();

        return redirect()
            ->route('parties.edit', $party)
            ->with('success', 'Ficha ampliada vinculada correctamente.');
    }

    protected function openOrConfirm(Request $request, Membership $membership)
    {
        $membership->loadMissing('party');

        if ($membership->party) {
            return redirect()
                ->route('parties.show', $membership->party)
                ->with('success', 'Ficha ampliada disponible.');
        }

        return redirect()->route('tenant.memberships.party.confirm', $membership);
    }

    protected function findMatchingParty(Membership $membership): ?Party
    {
        $email = $this->normalizedUserEmail($membership);

        if ($email === '') {
            return null;
        }

        return Party::query()
            ->where('tenant_id', $membership->tenant_id)
            ->where('email', $email)
            ->first();
    }

    protected function normalizedUserEmail(Membership $membership): string
    {
        $membership->loadMissing('user');

        return trim((string) $membership->user?->email);
    }

    protected function authorizeCreateEmployeeParty(Request $request): void
    {
        app(Security::class)->authorize(
            $request->user(),
            ModuleCatalog::PARTIES.'.create',
            Party::class,
            ['kind' => PartyCatalog::KIND_EMPLOYEE]
        );
    }
}
