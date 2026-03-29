{{-- FILE: resources/views/appointments/show.blade.php | V5 --}}

@extends('layouts.app')

@php
    use App\Support\Catalogs\AppointmentCatalog;
    use App\Support\Navigation\NavigationTrail;

    $appointmentTitle = AppointmentCatalog::rowTitleFor($appointment->kind, $appointment->work_mode);
    $referenceLabel = AppointmentCatalog::referenceLabelForKind($appointment->kind);

    $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
    $trailQuery = NavigationTrail::toQuery($navigationTrail);
    $backUrl = NavigationTrail::previousUrl(
        $navigationTrail,
        route('appointments.calendar', [
            'view' => 'month',
            'month' => now()->format('Y-m'),
        ]),
    );
@endphp

@section('title', $appointmentTitle)

@section('content')
    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$appointmentTitle">
            @if ($canEditAppointment)
                <a href="{{ route('appointments.edit', ['appointment' => $appointment] + $trailQuery) }}"
                    class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endif

            @if ($canDeleteAppointment)
                <form method="POST"
                    action="{{ route('appointments.destroy', ['appointment' => $appointment] + $trailQuery) }}"
                    class="inline-form" data-action="app-confirm-submit" data-confirm-message="¿Eliminar turno?">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endif

            @if ($appointment->order)
                <a href="{{ route('orders.show', ['order' => $appointment->order] + $trailQuery) }}"
                    class="btn btn-secondary">
                    Ver {{ strtolower(AppointmentCatalog::orderLabel()) }}
                </a>
            @elseif ($appointment->party_id)
                <a href="{{ route(
                    'orders.create',
                    [
                        'appointment_id' => $appointment->id,
                        'party_id' => $appointment->party_id,
                        'asset_id' => $appointment->asset_id,
                    ] + $trailQuery,
                ) }}"
                    class="btn btn-secondary">
                    Crear {{ strtolower(AppointmentCatalog::orderLabel()) }}
                </a>
            @else
                <span class="btn btn-secondary disabled" aria-disabled="true"
                    title="Asociá un {{ strtolower(AppointmentCatalog::contactLabel()) }} para poder crear una {{ strtolower(AppointmentCatalog::orderLabel()) }}.">
                    Crear {{ strtolower(AppointmentCatalog::orderLabel()) }}
                </span>
            @endif

            <a href="{{ route('appointments.print', ['appointment' => $appointment]) }}" class="btn btn-secondary"
                target="_blank">
                Imprimir
            </a>

            <a href="{{ route('appointments.pdf', ['appointment' => $appointment]) }}" class="btn btn-secondary">
                Descargar PDF
            </a>

            <a href="{{ $backUrl }}" class="btn btn-secondary btn-icon" title="Volver" aria-label="Volver">
                <x-icons.chevron-left />
            </a>
        </x-page-header>

        <x-show-summary details-id="appointment-more-detail">
            <x-show-summary-item :label="AppointmentCatalog::contactLabel()">
                @if ($appointment->party)
                    <a href="{{ route('parties.show', ['party' => $appointment->party] + $trailQuery) }}">
                        {{ $appointment->party->name }}
                    </a>
                @else
                    —
                @endif
            </x-show-summary-item>

            <x-show-summary-item :label="AppointmentCatalog::assetLabel()">
                @if ($appointment->asset)
                    <a href="{{ route('assets.show', ['asset' => $appointment->asset] + $trailQuery) }}">
                        {{ $appointment->asset->name }}
                    </a>
                @else
                    —
                @endif
            </x-show-summary-item>

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

            <x-show-summary-item :label="AppointmentCatalog::orderLabel()">
                @if ($appointment->order)
                    <a href="{{ route('orders.show', ['order' => $appointment->order] + $trailQuery) }}">
                        {{ $appointment->order->number ?: 'Orden #' . $appointment->order->id }}
                    </a>
                @elseif ($appointment->party_id)
                    <a
                        href="{{ route(
                            'orders.create',
                            [
                                'appointment_id' => $appointment->id,
                                'party_id' => $appointment->party_id,
                                'asset_id' => $appointment->asset_id,
                            ] + $trailQuery,
                        ) }}">
                        Crear {{ strtolower(AppointmentCatalog::orderLabel()) }}
                    </a>
                @else
                    Asociá un {{ strtolower(AppointmentCatalog::contactLabel()) }} para poder crear una
                    {{ strtolower(AppointmentCatalog::orderLabel()) }}.
                @endif
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
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">ID</span>
                        <div class="detail-block-value">#{{ $appointment->id }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Creado por</span>
                        <div class="detail-block-value">{{ $appointment->creator?->name ?: '—' }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Actualizado por</span>
                        <div class="detail-block-value">{{ $appointment->updater?->name ?: '—' }}</div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Notas</span>
                        <div class="detail-block-value">{{ $appointment->notes ?: '—' }}</div>
                    </div>
                </div>
            </x-slot:details>
        </x-show-summary>
    </x-page>
@endsection
