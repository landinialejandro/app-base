<?php

// FILE: app/Http/Controllers/TenantProfileController.php | V15

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Membership;
use App\Models\OperationalActivity;
use App\Models\Role;
use App\Models\SelfServiceStoreCustomer;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\BusinessTypeCatalog;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\PartyCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Catalogs\RoleCatalog;
use App\Support\Navigation\NavigationTrail;
use App\Support\Tenants\OperationalActivityChangePresenter;
use App\Support\Tenants\OperationalActivityLinkResolver;
use App\Support\Tenants\TenantProfileAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantProfileController extends Controller
{
    public function show(Request $request)
    {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->first();

        $tenantProfileAccess = app(TenantProfileAccess::class);

        abort_unless($tenantProfileAccess->canViewProfile($membership), 403);

        $isTenantOwner = $tenantProfileAccess->isOwner($membership);
        $isTenantAdmin = $tenantProfileAccess->isAdmin($membership);
        $canEditTenantGeneral = $tenantProfileAccess->canEditGeneral($membership);
        $canManageAdminRole = $tenantProfileAccess->canManagePermissionsForRole($membership, RoleCatalog::ADMIN);

        $canViewOperationalActivity = app(Security::class)
            ->allows(auth()->user(), 'operational_activity.viewAny');

        $canViewSelfServiceCustomers = $tenantProfileAccess->canViewProfile($membership);
        $canManageSelfServiceCustomers = $tenantProfileAccess->canManageSelfServiceCustomers($membership);

        $memberships = Membership::query()
            ->where('tenant_id', $tenant->id)
            ->with([
                'user',
                'roles' => function ($query) {
                    $query->orderBy('name');
                },
            ])
            ->orderByDesc('is_owner')
            ->orderBy('status')
            ->orderBy('id')
            ->get();

        $availableRoles = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('slug', RoleCatalog::assignable())
            ->orderByRaw('
    CASE slug
        WHEN ? THEN 1
        WHEN ? THEN 2
        WHEN ? THEN 3
        WHEN ? THEN 4
        ELSE 99
    END
', [
                RoleCatalog::ADMIN,
                RoleCatalog::SALES,
                RoleCatalog::OPERATOR,
                RoleCatalog::ADMINISTRATOR,
            ])
            ->orderBy('name')
            ->get();

        $allowedTabs = ['general', 'users', 'accesses', 'permissions'];

        if ($canViewSelfServiceCustomers) {
            $allowedTabs[] = 'self_service_customers';
        }

        if ($canViewOperationalActivity) {
            $allowedTabs[] = 'activity';
        }

        $activeTab = (string) $request->query(
            'return_tab',
            $request->query('tab', 'general')
        );

        if (! in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'general';
        }

        $generatedInvitation = null;
        $generatedInvitationId = session('generated_invitation_id');

        if ($generatedInvitationId) {
            $generatedInvitation = Invitation::query()
                ->where('tenant_id', $tenant->id)
                ->where('type', 'member_invite')
                ->where('id', $generatedInvitationId)
                ->first();
        }

        $pendingInvitations = Invitation::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', 'member_invite')
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get();

        $permissionRoles = [
            RoleCatalog::ADMIN => RoleCatalog::label(RoleCatalog::ADMIN),
            RoleCatalog::SALES => RoleCatalog::label(RoleCatalog::SALES),
            RoleCatalog::OPERATOR => RoleCatalog::label(RoleCatalog::OPERATOR),
            RoleCatalog::ADMINISTRATOR => RoleCatalog::label(RoleCatalog::ADMINISTRATOR),
        ];

        $selectedPermissionRole = (string) $request->query('role', RoleCatalog::ADMIN);

        if (! array_key_exists($selectedPermissionRole, $permissionRoles)) {
            $selectedPermissionRole = RoleCatalog::ADMIN;
        }

        $canEditSelectedPermissionRole = $tenantProfileAccess->canManagePermissionsForRole(
            $membership,
            $selectedPermissionRole
        );

        $enabledModules = collect(TenantModuleAccess::enabledModules($tenant))
            ->filter(fn ($enabled) => $enabled === true)
            ->keys()
            ->values()
            ->all();

        $moduleCapabilityMap = $this->buildModuleCapabilityMap($enabledModules);

        $moduleLabels = collect(array_keys($moduleCapabilityMap))
            ->mapWithKeys(fn ($module) => [$module => ModuleCatalog::label($module, $module)])
            ->all();

        $capabilityLabels = [
            CapabilityCatalog::VIEW_ANY => 'Ver lista',
            CapabilityCatalog::VIEW => 'Ver detalle',
            CapabilityCatalog::CREATE => 'Crear',
            CapabilityCatalog::UPDATE => 'Editar',
            CapabilityCatalog::DELETE => 'Eliminar',
        ];

        $scopeLabels = PermissionScopeCatalog::labels();

        $permissionMatrix = $this->buildPermissionMatrix(
            $tenant->id,
            $selectedPermissionRole,
            $moduleCapabilityMap
        );

        $scopeOptionsByModuleCapability = $this->buildScopeOptionsByModuleCapability($moduleCapabilityMap);
        $constraintOptionsByModuleCapability = $this->buildConstraintOptionsByModuleCapability($moduleCapabilityMap);

        $selfServiceStoreCustomers = collect();
        $selfServiceCustomerStatusFilter = (string) $request->query('self_service_customer_status', 'all');

        $selfServiceCustomerStatusOptions = [
            'all' => 'Todos',
            'active' => 'Activos',
            'blocked' => 'Bloqueados',
            'cancelled' => 'Cancelados',
            'email_confirmed' => 'Email confirmado',
            'identity_completed' => 'Identidad completa',
            'operation_enabled' => 'Operación habilitada',
            'operation_blocked' => 'Operación bloqueada',
        ];

        if (! array_key_exists($selfServiceCustomerStatusFilter, $selfServiceCustomerStatusOptions)) {
            $selfServiceCustomerStatusFilter = 'all';
        }

        $selfServiceCustomerStatusCounts = [
            'all' => 0,
            'active' => 0,
            'blocked' => 0,
            'cancelled' => 0,
            'email_confirmed' => 0,
            'identity_completed' => 0,
            'operation_enabled' => 0,
            'operation_blocked' => 0,
        ];

        if ($canViewSelfServiceCustomers) {
            $baseSelfServiceCustomerQuery = SelfServiceStoreCustomer::query()
                ->where('tenant_id', $tenant->id);

            $selfServiceCustomerStatusCounts = [
                'all' => (clone $baseSelfServiceCustomerQuery)->count(),
                'active' => (clone $baseSelfServiceCustomerQuery)
                    ->where('status', SelfServiceStoreCustomer::STATUS_ACTIVE)
                    ->count(),
                'blocked' => (clone $baseSelfServiceCustomerQuery)
                    ->where('status', SelfServiceStoreCustomer::STATUS_BLOCKED)
                    ->count(),
                'cancelled' => (clone $baseSelfServiceCustomerQuery)
                    ->where('status', SelfServiceStoreCustomer::STATUS_CANCELLED)
                    ->count(),
                'email_confirmed' => (clone $baseSelfServiceCustomerQuery)
                    ->where('identity_stage', SelfServiceStoreCustomer::IDENTITY_STAGE_EMAIL_CONFIRMED)
                    ->count(),
                'identity_completed' => (clone $baseSelfServiceCustomerQuery)
                    ->where('identity_stage', SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED)
                    ->count(),
                'operation_enabled' => (clone $baseSelfServiceCustomerQuery)
                    ->where('operation_enabled', true)
                    ->count(),
                'operation_blocked' => (clone $baseSelfServiceCustomerQuery)
                    ->where('operation_enabled', false)
                    ->count(),
            ];

            $selfServiceCustomerQuery = SelfServiceStoreCustomer::query()
                ->where('tenant_id', $tenant->id)
                ->with([
                    'account',
                    'party',
                ]);

            if ($selfServiceCustomerStatusFilter === 'active') {
                $selfServiceCustomerQuery->where('status', SelfServiceStoreCustomer::STATUS_ACTIVE);
            }

            if ($selfServiceCustomerStatusFilter === 'blocked') {
                $selfServiceCustomerQuery->where('status', SelfServiceStoreCustomer::STATUS_BLOCKED);
            }

            if ($selfServiceCustomerStatusFilter === 'cancelled') {
                $selfServiceCustomerQuery->where('status', SelfServiceStoreCustomer::STATUS_CANCELLED);
            }

            if ($selfServiceCustomerStatusFilter === 'email_confirmed') {
                $selfServiceCustomerQuery->where('identity_stage', SelfServiceStoreCustomer::IDENTITY_STAGE_EMAIL_CONFIRMED);
            }

            if ($selfServiceCustomerStatusFilter === 'identity_completed') {
                $selfServiceCustomerQuery->where('identity_stage', SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED);
            }

            if ($selfServiceCustomerStatusFilter === 'operation_enabled') {
                $selfServiceCustomerQuery->where('operation_enabled', true);
            }

            if ($selfServiceCustomerStatusFilter === 'operation_blocked') {
                $selfServiceCustomerQuery->where('operation_enabled', false);
            }

            $selfServiceStoreCustomers = $selfServiceCustomerQuery
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();
        }
        $operationalActivityRows = collect();

        if ($canViewOperationalActivity) {
            $linkResolver = app(OperationalActivityLinkResolver::class);
            $changePresenter = app(OperationalActivityChangePresenter::class);

            $activityTrail = NavigationTrail::base([
                NavigationTrail::makeNode(
                    'dashboard',
                    'dashboard',
                    'Inicio',
                    route('dashboard')
                ),
                NavigationTrail::makeNode(
                    'tenant-profile-activity',
                    $tenant->id,
                    'Perfil de empresa · Actividad',
                    route('tenant.profile.show', ['tab' => 'activity'])
                ),
            ]);

            $operationalActivityRows = OperationalActivity::query()
                ->where('tenant_id', $tenant->id)
                ->with(['actorUser', 'subjectUser', 'record'])
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(function ($activity) use ($linkResolver, $changePresenter, $activityTrail) {
                    $recordLink = $linkResolver->resolve($activity, $activityTrail);
                    $metadata = $activity->metadata ?? [];

                    return [
                        'id' => $activity->id,
                        'occurred_at' => $activity->occurred_at,
                        'module' => $activity->module,
                        'activity_type' => $activity->activity_type,
                        'record_label' => $recordLink['label'],
                        'record_url' => $recordLink['url'],
                        'actor_label' => $activity->actorUser?->name
                            ?? $activity->actorUser?->email
                            ?? 'Sistema',
                        'subject_label' => $activity->subjectUser?->name
                            ?? $activity->subjectUser?->email
                            ?? '—',
                        'metadata' => $metadata,
                        'change_summary' => $changePresenter->summary($metadata),
                        'change_details' => $changePresenter->details($metadata)->all(),
                    ];
                });
        }

        return view('tenants.profile', [
            'tenant' => $tenant,
            'memberships' => $memberships,
            'availableRoles' => $availableRoles,
            'activeTab' => $activeTab,
            'generatedInvitation' => $generatedInvitation,
            'pendingInvitations' => $pendingInvitations,
            'businessTypeLabels' => BusinessTypeCatalog::labels(),

            'permissionRoles' => $permissionRoles,
            'selectedPermissionRole' => $selectedPermissionRole,
            'moduleLabels' => $moduleLabels,
            'capabilityLabels' => $capabilityLabels,
            'scopeLabels' => $scopeLabels,
            'moduleCapabilityMap' => $moduleCapabilityMap,
            'permissionMatrix' => $permissionMatrix,
            'scopeOptionsByModuleCapability' => $scopeOptionsByModuleCapability,
            'constraintOptionsByModuleCapability' => $constraintOptionsByModuleCapability,

            'isTenantOwner' => $isTenantOwner,
            'isTenantAdmin' => $isTenantAdmin,
            'canEditTenantGeneral' => $canEditTenantGeneral,
            'canManageAdminRole' => $canManageAdminRole,
            'canEditSelectedPermissionRole' => $canEditSelectedPermissionRole,
            'actorMembership' => $membership,

            'canViewOperationalActivity' => $canViewOperationalActivity,
            'operationalActivityRows' => $operationalActivityRows,

            'canViewSelfServiceCustomers' => $canViewSelfServiceCustomers,
            'canManageSelfServiceCustomers' => $canManageSelfServiceCustomers,
            'selfServiceStoreCustomers' => $selfServiceStoreCustomers,
            'selfServiceCustomerStatusFilter' => $selfServiceCustomerStatusFilter,
            'selfServiceCustomerStatusOptions' => $selfServiceCustomerStatusOptions,
            'selfServiceCustomerStatusCounts' => $selfServiceCustomerStatusCounts,
        ]);
    }

    public function update(Request $request)
    {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->first();

        $tenantProfileAccess = app(TenantProfileAccess::class);

        abort_unless($tenantProfileAccess->canEditGeneral($membership), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'settings.legal_name' => ['nullable', 'string', 'max:255'],
            'settings.tax_id' => ['nullable', 'string', 'max:50'],
            'settings.email' => ['nullable', 'email', 'max:255'],
            'settings.phone' => ['nullable', 'string', 'max:100'],

            'settings.address' => ['nullable', 'string', 'max:255'],
            'settings.city' => ['nullable', 'string', 'max:150'],
            'settings.state' => ['nullable', 'string', 'max:150'],
            'settings.country' => ['nullable', 'string', 'max:150'],

            'settings.business_profile.type' => [
                'nullable',
                'string',
                Rule::in(BusinessTypeCatalog::all()),
            ],
        ]);

        $tenant->update([
            'name' => $data['name'],
            'settings' => array_replace_recursive(
                $tenant->settings ?? [],
                $data['settings'] ?? []
            ),
        ]);

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'general'])
            ->with('success', 'Perfil de empresa actualizado correctamente.');
    }

    protected function buildModuleCapabilityMap(array $enabledModules): array
    {
        if (empty($enabledModules)) {
            return [];
        }

        $map = [];

        foreach ($enabledModules as $module) {
            $capabilities = $this->capabilitiesForModule($module);

            if (! empty($capabilities)) {
                $map[$module] = $capabilities;
            }
        }

        return $map;
    }

    protected function capabilitiesForModule(string $module): array
    {
        return match ($module) {
            ModuleCatalog::DASHBOARD,
            ModuleCatalog::SERVICE_MAINTENANCE => [
                CapabilityCatalog::VIEW_ANY,
            ],

            ModuleCatalog::APPOINTMENTS,
            ModuleCatalog::ASSETS,
            ModuleCatalog::PRODUCTS,
            ModuleCatalog::INVENTORY,
            ModuleCatalog::DOCUMENTS,
            ModuleCatalog::PROJECTS,
            ModuleCatalog::TASKS,
            ModuleCatalog::ORDERS,
            ModuleCatalog::PARTIES => [
                CapabilityCatalog::VIEW_ANY,
                CapabilityCatalog::VIEW,
                CapabilityCatalog::CREATE,
                CapabilityCatalog::UPDATE,
                CapabilityCatalog::DELETE,
            ],

            default => [],
        };
    }

    protected function buildPermissionMatrix(string $tenantId, string $roleSlug, array $moduleCapabilityMap): array
    {
        $matrix = [];

        foreach ($moduleCapabilityMap as $module => $capabilities) {
            $matrix[$module] = [];

            foreach ($capabilities as $capability) {
                $matrix[$module][$capability] = [
                    'enabled' => false,
                    'scope' => null,
                    'execution_mode' => 'manual',
                    'constraints' => [],
                ];
            }
        }

        if (empty($moduleCapabilityMap)) {
            return $matrix;
        }

        $role = Role::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $roleSlug)
            ->with('permissions')
            ->first();

        if (! $role) {
            return $matrix;
        }

        $permissionsBySlug = $role->permissions
            ->keyBy('slug');

        foreach ($moduleCapabilityMap as $module => $capabilities) {
            foreach ($capabilities as $capability) {
                $slug = CapabilityCatalog::permissionSlug($module, $capability);
                $assignedPermission = $permissionsBySlug->get($slug);

                if (! $assignedPermission) {
                    continue;
                }

                $matrix[$module][$capability] = [
                    'enabled' => true,
                    'scope' => $assignedPermission->pivot->scope,
                    'execution_mode' => $assignedPermission->pivot->execution_mode ?: 'manual',
                    'constraints' => $this->normalizeConstraints($assignedPermission->pivot->constraints ?? null),
                ];
            }
        }

        return $matrix;
    }

    protected function buildScopeOptionsByModuleCapability(array $moduleCapabilityMap): array
    {
        $options = [];

        foreach ($moduleCapabilityMap as $module => $capabilities) {
            $options[$module] = [];

            foreach ($capabilities as $capability) {
                $options[$module][$capability] = PermissionScopeCatalog::optionsFor($module, $capability);
            }
        }

        return $options;
    }

    protected function buildConstraintOptionsByModuleCapability(array $moduleCapabilityMap): array
    {
        $options = [];

        foreach ($moduleCapabilityMap as $module => $capabilities) {
            $options[$module] = [];

            foreach ($capabilities as $capability) {
                $options[$module][$capability] = $this->buildConstraintOptionsFor($module, $capability);
            }
        }

        return $options;
    }

    protected function buildConstraintOptionsFor(string $module, string $capability): array
    {
        if (
            in_array($capability, [
                CapabilityCatalog::VIEW_ANY,
                CapabilityCatalog::VIEW,
                CapabilityCatalog::CREATE,
                CapabilityCatalog::UPDATE,
                CapabilityCatalog::DELETE,
            ], true)
        ) {
            if ($module === ModuleCatalog::ORDERS) {
                return [
                    'allowed_kinds' => OrderCatalog::groupLabels(),
                ];
            }

            if ($module === ModuleCatalog::PARTIES) {
                return [
                    'allowed_party_roles' => PartyCatalog::roleLabels(),
                ];
            }
        }

        return [];
    }

    protected function normalizeConstraints(mixed $constraints): array
    {
        if (is_array($constraints)) {
            return $constraints;
        }

        if (is_string($constraints) && trim($constraints) !== '') {
            $decoded = json_decode($constraints, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
