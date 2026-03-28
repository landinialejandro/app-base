{{-- FILE: resources/views/appointments/print.blade.php | V1 --}}
@extends('layouts.print')

@php
    use App\Support\Catalogs\AppointmentCatalog;

    $appointmentTitle = AppointmentCatalog::rowTitleFor($appointment->kind, $appointment->work_mode);
    $referenceLabel = AppointmentCatalog::referenceLabelForKind($appointment->kind);

    $whenLabel = match (true) {
        (bool) $appointment->is_all_day => ($appointment->scheduled_date?->format('d/m/Y') ?: '—') . ' · Día completo',
        $appointment->starts_at && $appointment->ends_at => ($appointment->scheduled_date?->format('d/m/Y') ?: '—') .
            ' · ' .
            $appointment->starts_at->format('H:i') .
            ' - ' .
            $appointment->ends_at->format('H:i'),
        default => ($appointment->scheduled_date?->format('d/m/Y') ?: '—') . ' · Sin horario',
    };
@endphp

@section('title', 'Ticket de turno')

@section('content')
    <div class="print-title-row">
        <div>
            <h2 class="print-title">Ticket de turno</h2>
            <div class="print-subtitle">{{ $appointmentTitle }}</div>
        </div>

        <div class="print-badge">
            {{ AppointmentCatalog::statusLabel($appointment->status) }}
        </div>
    </div>

    <section class="print-section">
        <h3 class="print-section-title">Datos principales</h3>

        <div class="print-grid">
            <div class="print-block">
                <div class="print-block-label">Turno</div>
                <div class="print-block-value">#{{ $appointment->id }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">{{ AppointmentCatalog::contactLabel() }}</div>
                <div class="print-block-value">{{ $appointment->party?->name ?: '—' }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">{{ AppointmentCatalog::assetLabel() }}</div>
                <div class="print-block-value">{{ $appointment->asset?->name ?: '—' }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">Cuándo</div>
                <div class="print-block-value">{{ $whenLabel }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">{{ AppointmentCatalog::assignedUserLabel() }}</div>
                <div class="print-block-value">{{ $appointment->assignedUser?->name ?: '—' }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">{{ AppointmentCatalog::orderLabel() }}</div>
                <div class="print-block-value">
                    {{ $appointment->order?->number ?: ($appointment->order ? 'Orden #' . $appointment->order->id : '—') }}
                </div>
            </div>

            <div class="print-block">
                <div class="print-block-label">Tipo</div>
                <div class="print-block-value">{{ AppointmentCatalog::kindLabel($appointment->kind) }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">{{ AppointmentCatalog::workPlaceLabel() }}</div>
                <div class="print-block-value">{{ AppointmentCatalog::workModeLabel($appointment->work_mode) ?: '—' }}
                </div>
            </div>

            <div class="print-block">
                <div class="print-block-label">{{ $referenceLabel }}</div>
                <div class="print-block-value">{{ $appointment->workstation_name ?: '—' }}</div>
            </div>
        </div>
    </section>

    <section class="print-section">
        <h3 class="print-section-title">Notas</h3>
        <div class="print-notes">{{ $appointment->notes ?: '—' }}</div>
    </section>
@endsection
