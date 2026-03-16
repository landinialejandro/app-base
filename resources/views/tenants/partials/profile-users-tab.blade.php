{{-- FILE: resources/views/tenants/partials/profile-users-tab.blade.php --}}

<section class="tab-panel {{ $activeTab === 'users' ? 'is-active' : '' }}" data-tab-panel="users"
    {{ $activeTab === 'users' ? '' : 'hidden' }}>
    <div class="tab-panel-stack">

        @include('tenants.partials.profile-invite-card', [
            'generatedInvitation' => $generatedInvitation,
        ])

        @include('tenants.partials.profile-pending-invitations', [
            'pendingInvitations' => $pendingInvitations,
        ])

        @include('tenants.partials.profile-memberships-table', [
            'memberships' => $memberships,
            'availableRoles' => $availableRoles,
        ])

    </div>
</section>
