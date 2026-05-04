{{-- FILE: resources/views/tenants/partials/profile-users-tab.blade.php | V3 --}}

@include('tenants.partials.profile-invite-card', [
    'generatedInvitation' => $generatedInvitation,
])

@include('tenants.partials.profile-pending-invitations', [
    'pendingInvitations' => $pendingInvitations,
])

@include('tenants.partials.profile-users-table', [
    'memberships' => $memberships,
])

<x-dev-component-version name="tenants.partials.profile-users-tab" version="V3" />