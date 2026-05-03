{{-- FILE: resources/views/tenants/partials/profile-activity-tab.blade.php | V4 --}}

@php
    $operationalActivityRows = $operationalActivityRows ?? collect();
@endphp

<section class="tab-panel {{ $activeTab === 'activity' ? 'is-active' : '' }}" data-tab-panel="activity"
    {{ $activeTab === 'activity' ? '' : 'hidden' }}>
    <div class="tab-panel-stack">
        @include('tenants.partials.operational-activity-table', [
            'operationalActivityRows' => $operationalActivityRows,
            'title' => 'Actividad operativa',
            'description' => 'Registro reciente de acciones operativas realizadas dentro de la empresa.',
            'emptyLabel' => 'Sin actividad registrada',
            'emptyMessage' => 'Todavía no hay actividad operativa registrada para esta empresa.',
        ])
    </div>
</section>