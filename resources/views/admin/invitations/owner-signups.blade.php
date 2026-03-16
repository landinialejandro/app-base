{{-- FILE: resources/views/admin/invitations/owner-signups.blade.php --}}

@extends('layouts.app')

@section('title', 'Invitaciones owner signup')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Administración', 'url' => route('admin.dashboard')],
            ['label' => 'Invitaciones owner signup'],
        ]" />

        <x-page-header title="Invitaciones owner signup">
            <div class="page-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                    Volver
                </a>
            </div>
        </x-page-header>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Pendientes del owner</h2>
                <p class="dashboard-section-text">
                    Invitaciones ya enviadas por superadmin y aún no aceptadas por el owner.
                </p>
            </div>

            @if ($invitations->count())
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Enviada</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Vencimiento</th>
                                <th>Solicitud</th>
                                <th>Acceso</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invitations as $invitation)
                                @php
                                    $invitationUrl = route('invitation.accept.show', $invitation->token);
                                    $expiresAt = $invitation->expires_at;
                                    $isExpired = $expiresAt && $expiresAt->isPast();
                                    $isExpiringSoon =
                                        $expiresAt && !$isExpired && now()->diffInHours($expiresAt, false) <= 48;

                                    $expirationBadgeClass = $isExpired
                                        ? 'status-badge status-badge--expired'
                                        : ($isExpiringSoon
                                            ? 'status-badge status-badge--expiring'
                                            : 'status-badge status-badge--sent');

                                    $expirationLabel = $isExpired
                                        ? 'Vencida'
                                        : ($isExpiringSoon
                                            ? 'Próxima a vencer'
                                            : 'Disponible');

                                    $humanDiff = $expiresAt
                                        ? ($isExpired
                                            ? 'Venció ' . $expiresAt->diffForHumans()
                                            : 'Vence ' . $expiresAt->diffForHumans())
                                        : null;
                                @endphp

                                <tr>
                                    <td>
                                        {{ $invitation->sent_at?->format('d/m/Y H:i') ?? $invitation->created_at?->format('d/m/Y H:i') }}
                                    </td>

                                    <td>{{ $invitation->email }}</td>

                                    <td>
                                        <span class="status-badge status-badge--pending">
                                            {{ $invitation->status }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($expiresAt)
                                            <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                                <span class="{{ $expirationBadgeClass }}">{{ $expirationLabel }}</span>
                                                <span>{{ $expiresAt->format('d/m/Y H:i') }}</span>
                                            </div>

                                            <div class="helper-inline" style="margin-top:0.35rem;">
                                                {{ $humanDiff }}
                                            </div>
                                        @else
                                            <span class="helper-inline">Sin vencimiento</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($invitation->signup_request_id)
                                            <a href="{{ route('admin.signup-requests.show', $invitation->signupRequest) }}">
                                                #{{ $invitation->signup_request_id }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="compact-actions-cell">
                                        <div class="compact-actions">
                                            <button type="button" class="btn btn-secondary btn-icon" title="Copiar link"
                                                aria-label="Copiar link" data-action="app-copy-value"
                                                data-copy-value="{{ $invitationUrl }}" data-copy-feedback="✓"
                                                data-copy-feedback-reset="">
                                                <x-icons.copy />
                                            </button>

                                            <a href="{{ $invitationUrl }}" class="btn btn-secondary btn-icon"
                                                target="_blank" title="Abrir link" aria-label="Abrir link">
                                                <x-icons.external-link />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $invitations->links() }}
                </div>
            @else
                <p class="mb-0">No hay invitaciones owner signup enviadas y pendientes del owner.</p>
            @endif
        </x-card>
    </x-page>
@endsection
