{{-- FILE: resources/views/tenants/profile.blade.php | V7 --}}

@extends('layouts.app')

@section('title', 'Perfil de empresa')

@section('content')
    @php
        $settings = $tenant->settings ?? [];
        $activeTab = $activeTab ?? 'general';
        $canViewOperationalActivity = $canViewOperationalActivity ?? false;
        $canEditTenantGeneral = $canEditTenantGeneral ?? false;
        $canEditSelectedPermissionRole = $canEditSelectedPermissionRole ?? false;
        $actorMembership = $actorMembership ?? null;

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
                    Desde este espacio se visualizan los datos de la empresa, usuarios, accesos, permisos y actividad.
                    Algunas acciones pueden mostrarse en modo lectura según tu nivel de autorización.
                </p>
            </div>
        </x-card>

        @include('tenants.partials.profile-summary', [
            'tenant' => $tenant,
        ])

        <x-host-tabs :items="$tabItems" :active-tab="$activeTab" :label="$tabsLabel" />

        <x-dev-component-version name="tenants.profile" version="V7" />
    </x-page>
@endsection