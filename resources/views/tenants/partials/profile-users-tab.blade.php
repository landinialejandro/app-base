{{-- FILE: resources/views/tenants/partials/profile-users-tab.blade.php | V2 --}}

<section class="tab-panel {{ $activeTab === 'users' ? 'is-active' : '' }}" data-tab-panel="users"
    {{ $activeTab === 'users' ? '' : 'hidden' }}>
    <div class="tab-panel-stack">

        @include('tenants.partials.profile-invite-card', [
            'generatedInvitation' => $generatedInvitation,
        ])

        @include('tenants.partials.profile-pending-invitations', [
            'pendingInvitations' => $pendingInvitations,
        ])

        @include('tenants.partials.profile-users-table', [
            'memberships' => $memberships,
        ])

    </div>
</section>
