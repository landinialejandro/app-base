{{-- FILE: resources/views/appointments/show.blade.php | V20 --}}

@extends('layouts.app')

@section('title', 'Detalle del turno')

@section('content')
    @php
        use App\Support\Catalogs\AppointmentCatalog;
        use App\Support\Appointments\AppointmentSurfaceService;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;

        $appointmentTitle = AppointmentCatalog::rowTitleFor($appointment->kind, $appointment->work_mode);
        $referenceLabel = AppointmentCatalog::referenceLabelForKind($appointment->kind);

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $tabsLabel = 'Secciones del turno';

        $backUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            route('appointments.calendar', [
                'view' => 'month',
                'month' => now()->format('Y-m'),
            ]),
        );

        $hostPack = app(AppointmentSurfaceService::class)->hostPack('appointments.show', $appointment, [
            'trailQuery' => $trailQuery,
        ]);

        $embedded = collect(app(ModuleSurfaceRegistry::class)->embeddedFor('appointments.show', $hostPack))->values();

        $linked = collect(app(ModuleSurfaceRegistry::class)->linkedFor('appointments.show', $hostPack))->values();

        $summaryItems = $linked->where('slot', 'summary_items')->values();
        $headerActions = $linked->where('slot', 'header_actions')->values();

        $detailItems = $embedded->where('slot', 'detail_items')->values();

        $tabItems = $embedded->where(fn($item) => ($item['slot'] ?? 'tab_panels') === 'tab_panels')->values();
        $requestedTab = (string) request()->query('return_tab', '');
        $availableTabKeys = $tabItems->pluck('key')->filter()->values()->all();

        $activeTab = in_array($requestedTab, $availableTabKeys, true)
            ? $requestedTab
            : $tabItems->first()['key'] ?? null;
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$appointmentTitle">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            @if ($canEditAppointment)
                <x-button-edit :href="route('appointments.edit', ['appointment' => $appointment] + $trailQuery)" />
            @endif

            @if ($canDeleteAppointment)
                <x-button-delete :action="route('appointments.destroy', ['appointment' => $appointment] + $trailQuery)" message="¿Eliminar turno?" />
            @endif

            <x-button-secondary :href="route('appointments.print', ['appointment' => $appointment])" target="_blank">
                Imprimir
            </x-button-secondary>

            <x-button-secondary :href="route('appointments.pdf', ['appointment' => $appointment])">
                Descargar PDF
            </x-button-secondary>

            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-show-summary details-id="appointment-more-detail">
            @foreach ($summaryItems as $surface)
                <x-show-summary-item :label="$surface['label'] ?? 'Relacionado'">
                    @include($surface['view'], $surface['data'] ?? [])
                </x-show-summary-item>
            @endforeach

            <x-show-summary-item label="Cuándo">
                @if ($appointment->is_all_day)
                    {{ $appointment->scheduled_date?->format('d/m/Y') ?? '—' }} · Día completo
                @elseif ($appointment->starts_at && $appointment->ends_at)
                    {{ $appointment->scheduled_date?->format('d/m/Y') ?? '—' }}
                    ·
                    {{ $appointment->starts_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }}
                @else
                    {{ $appointment->scheduled_date?->format('d/m/Y') ?? '—' }} · Sin horario
                @endif
            </x-show-summary-item>

            <x-show-summary-item :label="AppointmentCatalog::assignedUserLabel()">
                {{ $appointment->assignedUser?->name ?? '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Estado operativo">
                <div class="summary-badge-stack">
                    <span class="status-badge {{ AppointmentCatalog::badgeClass($appointment->status) }}">
                        Estado: {{ AppointmentCatalog::statusLabel($appointment->status) }}
                    </span>

                    <span class="status-badge status-badge--in-progress">
                        Tipo: {{ AppointmentCatalog::kindLabel($appointment->kind) }}
                    </span>
                </div>
            </x-show-summary-item>

            <x-show-summary-item :label="AppointmentCatalog::workPlaceLabel()">
                {{ AppointmentCatalog::workModeLabel($appointment->work_mode) }}
            </x-show-summary-item>

            <x-show-summary-item :label="$referenceLabel">
                {{ $appointment->workstation_name ?: '—' }}
            </x-show-summary-item>

            <x-slot:details>
                @foreach ($detailItems as $surface)
                    <x-show-summary-item-detail-block :label="$surface['label'] ?? 'Relacionado'">
                        @include($surface['view'], $surface['data'] ?? [])
                    </x-show-summary-item-detail-block>
                @endforeach

                <x-show-summary-item-detail-block label="ID">
                    #{{ $appointment->id }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Creado por">
                    {{ $appointment->creator?->name ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado por">
                    {{ $appointment->updater?->name ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $appointment->notes ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <x-host-tabs :items="$tabItems" :active-tab="$activeTab" :label="$tabsLabel" />
    </x-page>
@endsection
