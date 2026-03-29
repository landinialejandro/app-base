{{-- FILE: resources/views/appointments/print.blade.php | V2 --}}
@extends('layouts.print')

@php
    use App\Support\Catalogs\AppointmentCatalog;

    $appointmentTitle = AppointmentCatalog::rowTitleFor($appointment->kind, $appointment->work_mode);
    $referenceLabel = AppointmentCatalog::referenceLabelForKind($appointment->kind);

    $dateLabel = $appointment->scheduled_date?->format('d/m/Y') ?: '—';

    $timeLabel = match (true) {
        (bool) $appointment->is_all_day => 'Día completo',
        $appointment->starts_at && $appointment->ends_at => $appointment->starts_at->format('H:i') .
            ' - ' .
            $appointment->ends_at->format('H:i'),
        $appointment->starts_at => $appointment->starts_at->format('H:i'),
        default => 'Sin horario',
    };
@endphp

@section('title', 'Ticket de turno')

@section('content')
    <div class="ticket-print ticket-print--appointment">
        <div class="ticket-print__head">
            <div class="ticket-print__eyebrow">Ticket de turno</div>
            <h2 class="ticket-print__title">#{{ $appointment->id }} {{ $appointmentTitle }}</h2>
            <div class="ticket-print__status">
                <span class="ticket-print__pill">
                    {{ AppointmentCatalog::statusLabel($appointment->status) }}
                </span>
            </div>
        </div>

        <div class="ticket-print__hero">
            <div class="ticket-print__hero-block">
                <div class="ticket-print__hero-label">Fecha</div>
                <div class="ticket-print__hero-value">{{ $dateLabel }}</div>
            </div>

            <div class="ticket-print__hero-block">
                <div class="ticket-print__hero-label">Hora</div>
                <div class="ticket-print__hero-value">{{ $timeLabel }}</div>
            </div>
        </div>

        <div class="ticket-print__number ticket-print__number--split">
            <div class="ticket-print__split-item">
                <div class="ticket-print__focus-label">{{ AppointmentCatalog::contactLabel() }}</div>
                <div class="ticket-print__focus-value">{{ $appointment->party?->name ?: '—' }}</div>
            </div>

            <div class="ticket-print__split-item">
                <div class="ticket-print__focus-label">{{ AppointmentCatalog::assetLabel() }}</div>
                <div class="ticket-print__focus-value">{{ $appointment->asset?->name ?: '—' }}</div>
            </div>
        </div>

        <div class="ticket-print__section">

            <div class="ticket-print__row">
                <div class="ticket-print__label">{{ AppointmentCatalog::assignedUserLabel() }}</div>
                <div class="ticket-print__value">{{ $appointment->assignedUser?->name ?: '—' }}</div>
            </div>

            <div class="ticket-print__row">
                <div class="ticket-print__label">Tipo</div>
                <div class="ticket-print__value">{{ AppointmentCatalog::kindLabel($appointment->kind) }}</div>
            </div>

            <div class="ticket-print__row">
                <div class="ticket-print__label">{{ AppointmentCatalog::workPlaceLabel() }}</div>
                <div class="ticket-print__value">{{ AppointmentCatalog::workModeLabel($appointment->work_mode) ?: '—' }}
                </div>
            </div>

            <div class="ticket-print__row">
                <div class="ticket-print__label">{{ $referenceLabel }}</div>
                <div class="ticket-print__value">{{ $appointment->workstation_name ?: '—' }}</div>
            </div>

            <div class="ticket-print__row">
                <div class="ticket-print__label">{{ AppointmentCatalog::orderLabel() }}</div>
                <div class="ticket-print__value">
                    {{ $appointment->order?->number ?: ($appointment->order ? 'Orden #' . $appointment->order->id : '—') }}
                </div>
            </div>
        </div>

        <div class="ticket-print__notes">
            <div class="ticket-print__notes-label">Notas</div>
            <div class="ticket-print__notes-value">{{ $appointment->notes ?: '—' }}</div>
        </div>
    </div>
@endsection
