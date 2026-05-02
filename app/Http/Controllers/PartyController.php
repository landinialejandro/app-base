<?php

// FILE: app/Http/Controllers/PartyController.php | V12

namespace App\Http\Controllers;

use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PartyCatalog;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\PartyNavigationTrail;
use App\Support\Parties\PartyEmployeeContactAuthorization;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Party::class);

        $tenant = app('tenant');
        $security = app(Security::class);
        $user = $request->user();

        $q = trim((string) $request->get('q', ''));
        $kind = $request->get('kind');
        $role = $request->get('role');
        $isActive = $request->get('is_active');

        $partiesQuery = $security->scope(
            $user,
            ModuleCatalog::PARTIES.'.viewAny',
            Party::query()->with('roles')
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
            ->when($this->isValidPartyKind($kind), function ($query) use ($kind) {
                $query->where('kind', $kind);
            })
            ->when($this->isValidPartyRole($role), function ($query) use ($role) {
                $query->whereHas('roles', fn ($rolesQuery) => $rolesQuery->where('role', $role));
            })
            ->when($isActive !== null && $isActive !== '', function ($query) use ($isActive) {
                $query->where('is_active', (bool) $isActive);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $allowedPartyRoles = $this->resolvedAllowedPartyRolesFor('viewAny');
        $createPartyRoles = $this->resolvedAllowedPartyRolesFor('create');
        $canCreateByPartyRole = $this->resolveCreateAvailabilityByPartyRole($createPartyRoles);

        return view('parties.index', [
            'tenant' => $tenant,
            'parties' => $parties,
            'allowedKinds' => $this->partyKindOptions(),
            'allowedPartyRoles' => $allowedPartyRoles,
            'canCreateByPartyRole' => $canCreateByPartyRole,
        ]);
    }

    public function create(Request $request)
    {
        $tenant = app('tenant');
        $security = app(Security::class);
        $navigationTrail = PartyNavigationTrail::create($request);

        $createPartyRoles = $this->resolvedAllowedPartyRolesFor('create');
        $requestedRole = $request->get('role');
        $defaultRole = $this->resolveDefaultCreatablePartyRole($createPartyRoles, $requestedRole);

        abort_unless($defaultRole !== null, 403);

        $security->authorize(
            $request->user(),
            ModuleCatalog::PARTIES.'.create',
            Party::class,
            ['roles' => [$defaultRole]]
        );

        $requestedKind = $request->get('kind');
        $defaultKind = $this->resolveDefaultPartyKind($requestedKind);

        return view('parties.create', [
            'tenant' => $tenant,
            'navigationTrail' => $navigationTrail,
            'allowedKinds' => $this->partyKindOptions(),
            'allowedPartyRoles' => $createPartyRoles,
            'defaultKind' => $defaultKind,
            'defaultRole' => $defaultRole,
            'canManageEmployeeContacts' => $this->canManageEmployeeContacts($request),
        ]);
    }

public function store(StorePartyRequest $request)
{
    $security = app(Security::class);
    $data = $request->validated();
    $roles = $this->normalizePartyRoles($data['roles'] ?? []);
    unset($data['roles']);

    $kind = $data['kind'] ?? null;

    abort_unless($this->isValidPartyKind($kind), 403);

    $security->authorize(
        $request->user(),
        ModuleCatalog::PARTIES.'.create',
        Party::class,
        ['roles' => $roles]
    );

    $party = Party::create($data);
    $this->syncRoles($party, $roles);

    $createdRoles = $roles;
    sort($createdRoles);

    event(new \App\Events\OperationalRecordCreated(
        record: $party,
        actorUserId: auth()->id(),
        metadata: [
            'party_roles' => [
                'to' => $createdRoles,
            ],
        ],
    ));

    $navigationTrail = PartyNavigationTrail::show($request, $party);

    return redirect()
        ->route('parties.show', ['party' => $party] + NavigationTrail::toQuery($navigationTrail))
        ->with('success', "Contacto #{$party->id} creado correctamente.");
}

    public function show(Request $request, Party $party)
    {
        $this->authorize('view', $party);

        $tenant = app('tenant');
        $party->load('roles');
        $navigationTrail = PartyNavigationTrail::show($request, $party);

        $supportsAppointmentsModule = TenantModuleAccess::isEnabled(ModuleCatalog::APPOINTMENTS, $tenant);
        $supportsAssetsModule = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);
        $supportsOrdersModule = TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant);
        $supportsDocumentsModule = TenantModuleAccess::isEnabled(ModuleCatalog::DOCUMENTS, $tenant);

        return view('parties.show', [
            'tenant' => $tenant,
            'party' => $party,
            'navigationTrail' => $navigationTrail,
            'supportsAppointmentsModule' => $supportsAppointmentsModule,
            'supportsAssetsModule' => $supportsAssetsModule,
            'supportsOrdersModule' => $supportsOrdersModule,
            'supportsDocumentsModule' => $supportsDocumentsModule,
        ]);
    }

    public function edit(Request $request, Party $party)
    {
        $this->authorize('update', $party);

        $tenant = app('tenant');
        $party->load('roles');
        $navigationTrail = PartyNavigationTrail::edit($request, $party);
        $allowedPartyRoles = $this->resolvedAllowedPartyRolesFor('update', $party);

        return view('parties.edit', [
            'tenant' => $tenant,
            'party' => $party,
            'navigationTrail' => $navigationTrail,
            'allowedKinds' => $this->partyKindOptions(),
            'allowedPartyRoles' => $allowedPartyRoles,
            'canManageEmployeeContacts' => $this->canManageEmployeeContacts($request),
        ]);
    }

public function update(UpdatePartyRequest $request, Party $party)
{
    $this->authorize('update', $party);

    $security = app(Security::class);
    $data = $request->validated();
    $roles = $this->normalizePartyRoles($data['roles'] ?? []);
    unset($data['roles']);

    if ($party->hasActiveMembership()) {
        $data['kind'] = PartyCatalog::KIND_PERSON;

        if (! in_array(PartyCatalog::ROLE_EMPLOYEE, $roles, true)) {
            $roles[] = PartyCatalog::ROLE_EMPLOYEE;
        }

        $linkedMembership = $party->activeMemberships()
            ->with('user')
            ->first();

        if ($linkedMembership?->user?->email) {
            $data['email'] = $linkedMembership->user->email;
        }
    }

    $kind = $data['kind'] ?? null;

    abort_unless($this->isValidPartyKind($kind), 403);

    $security->authorize(
        $request->user(),
        ModuleCatalog::PARTIES.'.update',
        $party,
        ['roles' => $roles]
    );

    $beforeAttributes = $party->getAttributes();

    $beforeRoles = $party->roles()
        ->pluck('role')
        ->values()
        ->all();

    sort($beforeRoles);

    $party->update($data);
    $this->syncRoles($party, $roles);

    $afterRoles = $this->normalizePartyRoles($roles);
    sort($afterRoles);

    $extraChanges = [];

    if ($beforeRoles !== $afterRoles) {
        $extraChanges['party_roles'] = [
            'from' => $beforeRoles,
            'to' => $afterRoles,
        ];
    }

    event(new \App\Events\OperationalRecordUpdated(
        record: $party,
        beforeAttributes: $beforeAttributes,
        actorUserId: auth()->id(),
        metadata: [
            'extra_changes' => $extraChanges,
        ],
    ));

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

    protected function resolvedAllowedPartyRolesFor(string $capability, ?Party $party = null): array
    {
        $user = auth()->user();
        $security = app(Security::class);

        $subject = match ($capability) {
            'viewAny', 'create' => Party::class,
            default => $party,
        };

        if ($subject === null) {
            return [];
        }

        $inspection = $security->inspect(
            $user,
            ModuleCatalog::PARTIES.'.'.$capability,
            $subject
        );

        $constraints = $inspection['constraints'] ?? [];
        $allowedPartyRoles = $constraints['allowed_party_roles'] ?? [];

        if (! is_array($allowedPartyRoles) || empty($allowedPartyRoles)) {
            return [];
        }

        return array_values(array_filter(
            PartyCatalog::roles(),
            fn ($role) => in_array($role, $allowedPartyRoles, true)
        ));
    }

    protected function resolveCreateAvailabilityByPartyRole(array $allowedPartyRoles): array
    {
        $security = app(Security::class);
        $user = auth()->user();

        $result = [];

        foreach ($allowedPartyRoles as $role) {
            $result[$role] = $security->allows(
                $user,
                ModuleCatalog::PARTIES.'.create',
                Party::class,
                ['roles' => [$role]]
            );
        }

        return $result;
    }

    protected function resolveDefaultCreatablePartyRole(array $allowedPartyRoles, mixed $requestedRole): ?string
    {
        $creatableByRole = $this->resolveCreateAvailabilityByPartyRole($allowedPartyRoles);

        if (is_string($requestedRole) && ($creatableByRole[$requestedRole] ?? false) === true) {
            return $requestedRole;
        }

        foreach ($creatableByRole as $role => $allowed) {
            if ($allowed) {
                return $role;
            }
        }

        return null;
    }

    protected function resolveDefaultPartyKind(mixed $requestedKind): string
    {
        if ($this->isValidPartyKind($requestedKind)) {
            return $requestedKind;
        }

        return PartyCatalog::KIND_PERSON;
    }

    protected function normalizePartyRoles(array $roles): array
    {
        $normalizedRoles = array_values(array_unique(array_filter(
            $roles,
            fn ($role) => $this->isValidPartyRole($role)
        )));

        return empty($normalizedRoles)
            ? [PartyCatalog::ROLE_OTHER]
            : $normalizedRoles;
    }

    protected function syncRoles(Party $party, array $roles): void
    {
        $normalizedRoles = $this->normalizePartyRoles($roles);

        $party->roles()->delete();

        foreach ($normalizedRoles as $role) {
            $party->roles()->create([
                'tenant_id' => $party->tenant_id,
                'role' => $role,
            ]);
        }
    }

    protected function canManageEmployeeContacts(Request $request): bool
    {
        return app(PartyEmployeeContactAuthorization::class)
            ->allows($request->user());
    }

    protected function partyKindOptions(): array
    {
        return array_keys(PartyCatalog::kindLabels());
    }

    protected function isValidPartyKind(mixed $kind): bool
    {
        return is_string($kind)
            && in_array($kind, $this->partyKindOptions(), true);
    }

    protected function isValidPartyRole(mixed $role): bool
    {
        return is_string($role)
            && in_array($role, PartyCatalog::roles(), true);
    }
}
