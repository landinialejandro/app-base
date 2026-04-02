{{-- FILE: resources/views/tenants/profile.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Perfil de empresa')

@section('content')
    @php
        $settings = $tenant->settings ?? [];
        $activeTab = $activeTab ?? 'general';
    @endphp

    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Perfil de empresa']]" />

        <x-page-header title="Perfil de empresa" />

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
            </div>

            @include('tenants.partials.profile-general-tab', [
                'tenant' => $tenant,
                'settings' => $settings,
                'activeTab' => $activeTab,
                'businessTypeLabels' => $businessTypeLabels ?? [],
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
            ])

            @include('tenants.partials.permissions.tab', [
                'activeTab' => $activeTab,
                'selectedPermissionRole' => $selectedPermissionRole,
                'permissionRoles' => $permissionRoles,
                'permissionMatrix' => $permissionMatrix,
                'moduleLabels' => $moduleLabels,
                'capabilityLabels' => $capabilityLabels,
            ])
        </div>
    </x-page>
@endsection
