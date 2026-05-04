{{-- FILE: resources/views/tenants/partials/profile-activity-tab.blade.php | V5 --}}

@php
    $operationalActivityRows = $operationalActivityRows ?? collect();
@endphp

@include('tenants.partials.operational-activity-table', [
    'operationalActivityRows' => $operationalActivityRows,
    'title' => 'Actividad operativa',
    'description' => 'Registro reciente de acciones operativas realizadas dentro de la empresa.',
    'emptyLabel' => 'Sin actividad registrada',
    'emptyMessage' => 'Todavía no hay actividad operativa registrada para esta empresa.',
])

<x-dev-component-version name="tenants.partials.profile-activity-tab" version="V5" />