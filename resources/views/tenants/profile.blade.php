{{-- FILE: resources/views/tenants/profile.blade.php | V10 --}}

@extends('layouts.app')

@section('title', 'Perfil de empresa')

@section('content')
    @php
        $settings = $tenant->settings ?? [];
        $activeTab = $activeTab ?? 'general';
        $canViewOperationalActivity = $canViewOperationalActivity ?? false;
        $canViewSelfServiceCustomers = $canViewSelfServiceCustomers ?? false;
        $canEditTenantGeneral = $canEditTenantGeneral ?? false;
        $canEditSelectedPermissionRole = $canEditSelectedPermissionRole ?? false;
        $actorMembership = $actorMembership ?? null;
        $selfServiceCustomerStatusCounts = $selfServiceCustomerStatusCounts ?? [];
        $canManageSelfServiceCustomers = $canManageSelfServiceCustomers ?? false;

        $tabsLabel = 'Secciones del perfil de empresa';

        $tabItems = collect([
            [
                'key' => 'general',
                'label' => 'General',
                'view' => 'tenants.partials.profile-general-tab',
                'data' => [
                    'tenant' => $tenant,
                    'settings' => $settings,
                    'businessTypeLabels' => $businessTypeLabels ?? [],
                    'canEditTenantGeneral' => $canEditTenantGeneral,
                ],
            ],
            [
                'key' => 'users',
                'label' => 'Invitaciones y usuarios',
                'view' => 'tenants.partials.profile-users-tab',
                'data' => [
                    'tenant' => $tenant,
                    'memberships' => $memberships,
                    'generatedInvitation' => $generatedInvitation ?? null,
                    'pendingInvitations' => $pendingInvitations ?? collect(),
                ],
            ],
            [
                'key' => 'accesses',
                'label' => 'Roles y acceso',
                'view' => 'tenants.partials.profile-accesses-tab',
                'data' => [
                    'tenant' => $tenant,
                    'memberships' => $memberships,
                    'availableRoles' => $availableRoles,
                    'actorMembership' => $actorMembership,
                ],
            ],
            [
                'key' => 'permissions',
                'label' => 'Permisos',
                'view' => 'tenants.partials.permissions.tab',
                'data' => [
                    'selectedPermissionRole' => $selectedPermissionRole,
                    'permissionRoles' => $permissionRoles,
                    'permissionMatrix' => $permissionMatrix,
                    'moduleLabels' => $moduleLabels,
                    'capabilityLabels' => $capabilityLabels,
                    'scopeOptionsByModuleCapability' => $scopeOptionsByModuleCapability,
                    'constraintOptionsByModuleCapability' => $constraintOptionsByModuleCapability,
                    'canEditSelectedPermissionRole' => $canEditSelectedPermissionRole,
                ],
            ],
        ]);

        if ($canViewSelfServiceCustomers) {
            $tabItems->push([
                'key' => 'self_service_customers',
                'label' => 'Clientes Tienda',
                'count' => (int) ($selfServiceCustomerStatusCounts['all'] ?? 0),
                'view' => 'tenants.partials.profile-self-service-customers-tab',
                'data' => [
                    'selfServiceStoreCustomers' => $selfServiceStoreCustomers ?? collect(),
                    'selfServiceCustomerStatusFilter' => $selfServiceCustomerStatusFilter ?? 'all',
                    'selfServiceCustomerStatusOptions' => $selfServiceCustomerStatusOptions ?? [],
                    'selfServiceCustomerStatusCounts' => $selfServiceCustomerStatusCounts,
                    'canManageSelfServiceCustomers' => $canManageSelfServiceCustomers ?? false,
                ],
            ]);
        }

        if ($canViewOperationalActivity) {
            $tabItems->push([
                'key' => 'activity',
                'label' => 'Actividad',
                'icon' => 'activity',
                'view' => 'tenants.partials.profile-activity-tab',
                'data' => [
                    'operationalActivityRows' => $operationalActivityRows ?? collect(),
                ],
            ]);
        }
    @endphp

    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Perfil de empresa']]" />

        <x-page-header title="Perfil de empresa" />

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Gestión de empresa</h2>
                <p class="dashboard-section-text">
                    Desde este espacio se visualizan los datos de la empresa, usuarios, accesos, permisos, clientes de
                    tienda y actividad.
                    Algunas acciones pueden mostrarse en modo lectura según tu nivel de autorización.
                </p>
            </div>
        </x-card>

        @include('tenants.partials.profile-summary', [
            'tenant' => $tenant,
        ])

        <x-host-tabs :items="$tabItems" :active-tab="$activeTab" :label="$tabsLabel" />

        <x-dev-component-version name="tenants.profile" version="V10" />
    </x-page>
@endsection
