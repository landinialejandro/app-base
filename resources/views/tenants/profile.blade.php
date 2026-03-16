{{-- FILE: resources/views/tenants/profile.blade.php --}}

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
                    Usuarios y accesos
                </button>
            </div>

            @include('tenants.partials.profile-general-tab', [
                'tenant' => $tenant,
                'settings' => $settings,
                'activeTab' => $activeTab,
            ])

            @include('tenants.partials.profile-users-tab', [
                'tenant' => $tenant,
                'memberships' => $memberships,
                'availableRoles' => $availableRoles,
                'generatedInvitation' => $generatedInvitation ?? null,
                'pendingInvitations' => $pendingInvitations ?? collect(),
                'activeTab' => $activeTab,
            ])
        </div>
    </x-page>
@endsection
