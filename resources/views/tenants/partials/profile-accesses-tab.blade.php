{{-- FILE: resources/views/tenants/partials/profile-accesses-tab.blade.php | V3 --}}

@include('tenants.partials.profile-memberships-table', [
    'memberships' => $memberships,
    'availableRoles' => $availableRoles,
    'actorMembership' => $actorMembership ?? null,
])

<x-dev-component-version name="tenants.partials.profile-accesses-tab" version="V3" />