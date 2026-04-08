<?php

// FILE: app/Http/Controllers/PartyController.php | V5

namespace App\Http\Controllers;

use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PartyCatalog;
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

        $partiesQuery = app(Security::class)->scope(
            auth()->user(),
            ModuleCatalog::PARTIES.'.viewAny',
            Party::query()
        );

        $parties = $partiesQuery
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

        $resolvedKinds = $this->resolvedAllowedKinds();

        return view('parties.index', [
            'tenant' => $tenant,
            'parties' => $parties,
            'allowedKinds' => $resolvedKinds,
            'canCreateByKind' => $this->resolveCreateAvailabilityByKind($resolvedKinds),
        ]);
    }

    public function create(Request $request)
    {
        $tenant = app('tenant');
        $navigationTrail = PartyNavigationTrail::create($request);
        $resolvedKinds = $this->resolvedAllowedKinds();

        $requestedKind = $request->get('kind');
        $defaultKind = $this->resolveDefaultCreatableKind($resolvedKinds, $requestedKind);

        abort_unless($defaultKind !== null, 403);

        abort_unless(
            app(Security::class)->allows(
                $request->user(),
                ModuleCatalog::PARTIES.'.create',
                Party::class,
                ['kind' => $defaultKind]
            ),
            403
        );

        return view('parties.create', [
            'tenant' => $tenant,
            'navigationTrail' => $navigationTrail,
            'allowedKinds' => $resolvedKinds,
            'defaultKind' => $defaultKind,
        ]);
    }

    public function store(StorePartyRequest $request)
    {
        $data = $request->validated();
        $kind = $data['kind'] ?? null;

        abort_unless(
            is_string($kind) && app(Security::class)->allows(
                $request->user(),
                ModuleCatalog::PARTIES.'.create',
                Party::class,
                ['kind' => $kind]
            ),
            403
        );

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
        $navigationTrail = PartyNavigationTrail::show($request, $party);

        return view('parties.show', [
            'tenant' => $tenant,
            'party' => $party,
            'navigationTrail' => $navigationTrail,
        ]);
    }

    public function edit(Request $request, Party $party)
    {
        $this->authorize('update', $party);

        $tenant = app('tenant');
        $navigationTrail = PartyNavigationTrail::edit($request, $party);
        $resolvedKinds = $this->resolvedAllowedKinds();

        return view('parties.edit', [
            'tenant' => $tenant,
            'party' => $party,
            'navigationTrail' => $navigationTrail,
            'allowedKinds' => $resolvedKinds,
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

    protected function resolvedAllowedKinds(): array
    {
        $tenant = app('tenant');
        $user = auth()->user();

        $constraints = app(Security::class)->inspect(
            $user,
            ModuleCatalog::PARTIES.'.viewAny',
            Party::class
        )['constraints'] ?? [];

        $allowedKinds = $constraints['allowed_kinds'] ?? [];

        if (! is_array($allowedKinds) || empty($allowedKinds)) {
            return array_keys(PartyCatalog::kindLabels());
        }

        return array_values(array_filter(
            array_keys(PartyCatalog::kindLabels()),
            fn ($kind) => in_array($kind, $allowedKinds, true)
        ));
    }

    protected function resolveCreateAvailabilityByKind(array $allowedKinds): array
    {
        $security = app(Security::class);
        $user = auth()->user();

        $result = [];

        foreach ($allowedKinds as $kind) {
            $result[$kind] = $security->allows(
                $user,
                ModuleCatalog::PARTIES.'.create',
                Party::class,
                ['kind' => $kind]
            );
        }

        return $result;
    }

    protected function resolveDefaultCreatableKind(array $allowedKinds, mixed $requestedKind): ?string
    {
        $creatableByKind = $this->resolveCreateAvailabilityByKind($allowedKinds);

        if (is_string($requestedKind) && ($creatableByKind[$requestedKind] ?? false) === true) {
            return $requestedKind;
        }

        foreach ($creatableByKind as $kind => $allowed) {
            if ($allowed) {
                return $kind;
            }
        }

        return null;
    }
}
