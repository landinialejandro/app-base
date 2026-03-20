@extends('layouts.app')

@php
    use App\Support\Catalogs\AppointmentCatalog;

    $appointmentTitle = match (true) {
        $appointment->kind === AppointmentCatalog::KIND_BLOCK => 'Bloqueo de agenda',
        $appointment->kind === AppointmentCatalog::KIND_VISIT => 'Turno de visita',
        $appointment->work_mode === AppointmentCatalog::WORK_MODE_FIELD_ASSISTANCE => 'Turno de asistencia externa',
        default => 'Turno de taller',
    };

    $referenceLabel = AppointmentCatalog::referenceLabelForKind($appointment->kind);
@endphp

@section('title', $appointmentTitle)

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Turnos', 'url' => route('appointments.index')],
            ['label' => $appointmentTitle],
        ]" />

        <x-page-header :title="$appointmentTitle">
            @if ($canEditAppointment)
                <a href="{{ route('appointments.edit', $appointment) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endif

            @if ($canDeleteAppointment)
                <form method="POST" action="{{ route('appointments.destroy', $appointment) }}" class="inline-form"
                    data-action="app-confirm-submit" data-confirm-message="¿Eliminar turno?">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endif

            @if ($appointment->order)
                <a href="{{ route('orders.show', $appointment->order) }}" class="btn btn-secondary">
                    Ver orden
                </a>
            @elseif ($appointment->party_id)
                <a href="{{ route('orders.create', [
                    'appointment_id' => $appointment->id,
                    'party_id' => $appointment->party_id,
                    'asset_id' => $appointment->asset_id,
                ]) }}"
                    class="btn btn-secondary">
                    Crear orden
                </a>
            @else
                <span class="btn btn-secondary disabled" aria-disabled="true"
                    title="Asociá un contacto al turno para poder crear una orden.">
                    Crear orden
                </span>
            @endif

            <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                Volver al listado
            </a>
        </x-page-header>

        <x-show-summary details-id="appointment-more-detail">
            <x-show-summary-item label="A quién le doy el turno">
                @if ($appointment->party)
                    <a href="{{ route('parties.show', $appointment->party) }}">
                        {{ $appointment->party->name }}
                    </a>
                @else
                    —
                @endif
            </x-show-summary-item>

            <x-show-summary-item label="Qué voy a ver">
                @if ($appointment->asset)
                    <a href="{{ route('assets.show', $appointment->asset) }}">
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

            <x-show-summary-item label="Quién lo va a realizar">
                {{ $appointment->assignedUser?->name ?? '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Con qué orden">
                @if ($appointment->order)
                    <a href="{{ route('orders.show', $appointment->order) }}">
                        {{ $appointment->order->number ?: 'Orden #' . $appointment->order->id }}
                    </a>
                @elseif ($appointment->party_id)
                    <a
                        href="{{ route('orders.create', [
                            'appointment_id' => $appointment->id,
                            'party_id' => $appointment->party_id,
                            'asset_id' => $appointment->asset_id,
                        ]) }}">
                        Crear orden
                    </a>
                @else
                    Asociá un contacto para poder crear una orden.
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

            <x-show-summary-item label="Lugar de trabajo">
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
                        <span class="detail-block-label">{{ $referenceLabel }}</span>
                        <div class="detail-block-value">{{ $appointment->workstation_name ?: '—' }}</div>
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
