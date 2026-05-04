{{-- FILE: resources/views/tenants/partials/profile-accesses-tab.blade.php | V2 --}}

<section class="tab-panel {{ $activeTab === 'accesses' ? 'is-active' : '' }}" data-tab-panel="accesses"
    {{ $activeTab === 'accesses' ? '' : 'hidden' }}>
    <div class="tab-panel-stack">

        @include('tenants.partials.profile-memberships-table', [
            'memberships' => $memberships,
            'availableRoles' => $availableRoles,
            'actorMembership' => $actorMembership ?? null,
        ])

    </div>
</section>