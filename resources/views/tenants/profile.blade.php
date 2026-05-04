{{-- FILE: resources/views/tenants/profile.blade.php | V6 --}}

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

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Perfil de empresa">
                <button type="button" class="tabs-link {{ $activeTab === 'general' ? 'is-active' : '' }}"
                    data-tab-link="general" role="tab" aria-selected="{{ $activeTab === 'general' ? 'true' : 'false' }}">
                    General
                </button>

                <button type="button" class="tabs-link {{ $activeTab === 'users' ? 'is-active' : '' }}"
                    data-tab-link="users" role="tab" aria-selected="{{ $activeTab === 'users' ? 'true' : 'false' }}">
                    Invitaciones y usuarios
                </button>

                <button type="button" class="tabs-link {{ $activeTab === 'accesses' ? 'is-active' : '' }}"
                    data-tab-link="accesses" role="tab"
                    aria-selected="{{ $activeTab === 'accesses' ? 'true' : 'false' }}">
                    Roles y acceso
                </button>

                <button type="button" class="tabs-link {{ $activeTab === 'permissions' ? 'is-active' : '' }}"
                    data-tab-link="permissions" role="tab"
                    aria-selected="{{ $activeTab === 'permissions' ? 'true' : 'false' }}">
                    Permisos
                </button>

                @if ($canViewOperationalActivity)
                    <button type="button" class="tabs-link {{ $activeTab === 'activity' ? 'is-active' : '' }}"
                        data-tab-link="activity" role="tab"
                        aria-selected="{{ $activeTab === 'activity' ? 'true' : 'false' }}">
                        Actividad
                    </button>
                @endif
            </div>

            @include('tenants.partials.profile-general-tab', [
                'tenant' => $tenant,
                'settings' => $settings,
                'activeTab' => $activeTab,
                'businessTypeLabels' => $businessTypeLabels ?? [],
                'canEditTenantGeneral' => $canEditTenantGeneral,
            ])

            @include('tenants.partials.profile-users-tab', [
                'tenant' => $tenant,
                'memberships' => $memberships,
                'generatedInvitation' => $generatedInvitation ?? null,
                'pendingInvitations' => $pendingInvitations ?? collect(),
                'activeTab' => $activeTab,
            ])

            @include('tenants.partials.profile-accesses-tab', [
                'tenant' => $tenant,
                'memberships' => $memberships,
                'availableRoles' => $availableRoles,
                'activeTab' => $activeTab,
                'actorMembership' => $actorMembership,
            ])

            @include('tenants.partials.permissions.tab', [
                'activeTab' => $activeTab,
                'selectedPermissionRole' => $selectedPermissionRole,
                'permissionRoles' => $permissionRoles,
                'permissionMatrix' => $permissionMatrix,
                'moduleLabels' => $moduleLabels,
                'capabilityLabels' => $capabilityLabels,
                'scopeOptionsByModuleCapability' => $scopeOptionsByModuleCapability,
                'constraintOptionsByModuleCapability' => $constraintOptionsByModuleCapability,
                'canEditSelectedPermissionRole' => $canEditSelectedPermissionRole,
            ])

            @include('tenants.partials.profile-activity-tab', [
                'activeTab' => $activeTab,
                'operationalActivityRows' => $operationalActivityRows ?? collect(),
            ])
        </div>
    </x-page>
@endsection