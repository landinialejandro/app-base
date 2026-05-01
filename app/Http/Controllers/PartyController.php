<?php

// FILE: app/Http/Controllers/PartyController.php | V11

namespace App\Http\Controllers;

use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PartyCatalog;
use App\Support\Catalogs\RoleCatalog;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\PartyNavigationTrail;
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
            ->when($kind, function ($query) use ($kind) {
                $query->where('kind', $kind);
            })
            ->when($role, function ($query) use ($role) {
                $query->whereHas('roles', fn ($rolesQuery) => $rolesQuery->where('role', $role));
            })
            ->when($isActive !== null && $isActive !== '', function ($query) use ($isActive) {
                $query->where('is_active', (bool) $isActive);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $visibleKinds = $this->resolvedAllowedKindsFor('viewAny');
        $createKinds = $this->resolvedAllowedKindsFor('create');
        $canCreateByKind = $this->resolveCreateAvailabilityByKind($createKinds);

        return view('parties.index', [
            'tenant' => $tenant,
            'parties' => $parties,
            'allowedKinds' => $visibleKinds,
            'canCreateByKind' => $canCreateByKind,
        ]);
    }

    public function create(Request $request)
    {
        $tenant = app('tenant');
        $security = app(Security::class);
        $navigationTrail = PartyNavigationTrail::create($request);

        $createKinds = $this->resolvedAllowedKindsFor('create');
        $requestedKind = $request->get('kind');
        $defaultKind = $this->resolveDefaultCreatableKind($createKinds, $requestedKind);

        abort_unless($defaultKind !== null, 403);

        $security->authorize(
            $request->user(),
            ModuleCatalog::PARTIES.'.create',
            Party::class,
            ['kind' => $defaultKind]
        );

        return view('parties.create', [
            'tenant' => $tenant,
            'navigationTrail' => $navigationTrail,
            'allowedKinds' => $createKinds,
            'defaultKind' => $defaultKind,
            'canManageEmployeeContacts' => $this->canManageEmployeeContacts($request),
        ]);
    }

    public function store(StorePartyRequest $request)
    {
        $security = app(Security::class);
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $kind = $data['kind'] ?? null;

        abort_unless(is_string($kind) && $kind !== '', 403);

        $security->authorize(
            $request->user(),
            ModuleCatalog::PARTIES.'.create',
            Party::class,
            ['kind' => $kind]
        );

        $party = Party::create($data);
        $this->syncRoles($party, $roles);

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
        $allowedKinds = $this->resolvedAllowedKindsFor('update', $party);

        return view('parties.edit', [
            'tenant' => $tenant,
            'party' => $party,
            'navigationTrail' => $navigationTrail,
            'allowedKinds' => $allowedKinds,
            'canManageEmployeeContacts' => $this->canManageEmployeeContacts($request),
        ]);
    }

    public function update(UpdatePartyRequest $request, Party $party)
    {
        $this->authorize('update', $party);

        $data = $request->validated();
        $roles = $data['roles'] ?? [];
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
        $allowedKinds = $this->resolvedAllowedKindsFor('update', $party);

        abort_unless(
            is_string($kind) && in_array($kind, $allowedKinds, true),
            403
        );

        $party->update($data);
        $this->syncRoles($party, $roles);

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

    protected function resolvedAllowedKindsFor(string $capability, ?Party $party = null): array
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

    protected function syncRoles(Party $party, array $roles): void
    {
        $normalizedRoles = array_values(array_unique(array_filter(
            $roles,
            fn ($role) => is_string($role) && in_array($role, PartyCatalog::roles(), true)
        )));

        if (empty($normalizedRoles)) {
            $normalizedRoles = [PartyCatalog::ROLE_OTHER];
        }

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
        $tenant = app('tenant');
        $user = $request->user();

        if (! $tenant || ! $user) {
            return false;
        }

        $membership = $user->memberships()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('roles')
            ->first();

        if (! $membership) {
            return false;
        }

        if ($membership->is_owner) {
            return true;
        }

        return $membership->roles->contains(
            fn ($role) => $role->slug === RoleCatalog::ADMIN
        );
    }
}