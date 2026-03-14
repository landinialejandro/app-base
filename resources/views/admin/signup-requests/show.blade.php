{{-- FILE: resources/views/admin/signup-requests/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle de solicitud')

@section('content')
    @php
        $backRoute = $signupRequest->status === 'pending'
            ? route('admin.signup-requests.index')
            : route('admin.signup-requests.processed');

        $statusClass = match ($signupRequest->status) {
            'pending' => 'status-badge status-badge--pending',
            'approved' => 'status-badge status-badge--approved',
            'rejected' => 'status-badge status-badge--rejected',
            default => 'status-badge',
        };
    @endphp

    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Administración', 'url' => route('admin.dashboard')],
            ['label' => $signupRequest->status === 'pending' ? 'Solicitudes pendientes' : 'Solicitudes procesadas', 'url' => $backRoute],
            ['label' => 'Detalle'],
        ]" />

        <x-page-header title="Detalle de solicitud">
            <div class="page-actions">
                <a href="{{ $backRoute }}" class="btn btn-secondary">
                    Volver
                </a>
            </div>
        </x-page-header>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Solicitud</h2>
                <p class="dashboard-section-text">Datos recibidos desde el formulario público.</p>
            </div>

            <div class="detail-grid">
                <div class="detail-block">
                    <span class="detail-block-label">Nombre</span>
                    <div class="detail-block-value">{{ $signupRequest->requested_name }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Email</span>
                    <div class="detail-block-value">{{ $signupRequest->requested_email }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Empresa</span>
                    <div class="detail-block-value">{{ $signupRequest->company_name }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Teléfono / WhatsApp</span>
                    <div class="detail-block-value">{{ $signupRequest->phone_whatsapp }}</div>
                </div>
            </div>

            <hr style="margin: 1rem 0; border: 0; border-top: 1px solid #d9e1ec;">

            <div class="detail-grid">
                <div class="detail-block">
                    <span class="detail-block-label">Estado</span>
                    <div class="detail-block-value">
                        <span class="{{ $statusClass }}">
                            {{ $signupRequest->status }}
                        </span>
                    </div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Fecha de creación</span>
                    <div class="detail-block-value">{{ $signupRequest->created_at?->format('d/m/Y H:i') }}</div>
                </div>

                @if ($signupRequest->approved_at)
                    <div class="detail-block">
                        <span class="detail-block-label">Fecha de aprobación</span>
                        <div class="detail-block-value">{{ $signupRequest->approved_at->format('d/m/Y H:i') }}</div>
                    </div>
                @endif

                @if ($signupRequest->rejected_at)
                    <div class="detail-block">
                        <span class="detail-block-label">Fecha de rechazo</span>
                        <div class="detail-block-value">{{ $signupRequest->rejected_at->format('d/m/Y H:i') }}</div>
                    </div>
                @endif

                <div class="detail-block detail-block--full">
                    <span class="detail-block-label">Notas</span>
                    <div class="detail-block-value">{{ $signupRequest->review_notes ?: 'Sin notas.' }}</div>
                </div>
            </div>

            @if ($ownerInvitation)
                @php
                    $invitationUrl = route('invitation.accept.show', $ownerInvitation->token);
                    $expiresAt = $ownerInvitation->expires_at;
                    $isExpired = $expiresAt && $expiresAt->isPast();
                    $isExpiringSoon = $expiresAt && !$isExpired && now()->diffInHours($expiresAt, false) <= 48;

                    $expirationBadgeClass = $isExpired
                        ? 'status-badge status-badge--expired'
                        : ($isExpiringSoon ? 'status-badge status-badge--expiring' : 'status-badge status-badge--sent');

                    $expirationLabel = $isExpired
                        ? 'Vencida'
                        : ($isExpiringSoon ? 'Próxima a vencer' : 'Disponible');

                    $humanDiff = $expiresAt
                        ? ($isExpired ? 'Venció ' . $expiresAt->diffForHumans() : 'Vence ' . $expiresAt->diffForHumans())
                        : null;
                @endphp

                <hr style="margin: 1rem 0; border: 0; border-top: 1px solid #d9e1ec;">

                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Invitación generada</h2>
                    <p class="dashboard-section-text">Resultado del proceso de aprobación.</p>
                </div>

                <div class="detail-grid">
                    <div class="detail-block">
                        <span class="detail-block-label">Tipo</span>
                        <div class="detail-block-value">{{ $ownerInvitation->type }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Estado</span>
                        <div class="detail-block-value">
                            <span class="status-badge status-badge--pending">
                                {{ $ownerInvitation->status }}
                            </span>
                        </div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Email</span>
                        <div class="detail-block-value">{{ $ownerInvitation->email }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Enviada por superadmin</span>
                        <div class="detail-block-value">
                            {{ $ownerInvitation->sent_at ? $ownerInvitation->sent_at->format('d/m/Y H:i') : 'No enviada aún' }}
                        </div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Vencimiento</span>
                        <div class="detail-block-value">
                            @if ($expiresAt)
                                <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                    <span class="{{ $expirationBadgeClass }}">{{ $expirationLabel }}</span>
                                    <span>{{ $expiresAt->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="helper-inline" style="margin-top:0.35rem;">
                                    {{ $humanDiff }}
                                </div>
                            @else
                                Sin vencimiento
                            @endif
                        </div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Token</span>
                        <div class="detail-block-value">{{ $ownerInvitation->token }}</div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label" for="owner-invitation-link">Link de acceso</span>
                        <input id="owner-invitation-link" type="text" class="form-control" value="{{ $invitationUrl }}" readonly
                            onclick="this.select();">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="
                                    const input = document.getElementById('owner-invitation-link');
                                    input.removeAttribute('readonly');
                                    input.select();
                                    input.setSelectionRange(0, 99999);
                                    document.execCommand('copy');
                                    input.setAttribute('readonly', 'readonly');
                                    this.textContent = 'Link copiado';
                                    setTimeout(() => this.textContent = 'Copiar link', 1500);
                                ">
                        Copiar link
                    </button>

                    <a href="{{ $invitationUrl }}" class="btn btn-secondary" target="_blank">
                        Abrir link
                    </a>

                    @if (!$ownerInvitation->sent_at)
                        <form method="POST" action="{{ route('admin.invitations.mark-as-sent', $ownerInvitation) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                Marcar como enviada
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('admin.invitations.owner-signups') }}" class="btn btn-secondary">
                        Ver invitaciones owner signup
                    </a>
                </div>
            @endif

            @if ($signupRequest->status === 'pending')
                <hr style="margin: 1rem 0; border: 0; border-top: 1px solid #d9e1ec;">

                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Acciones</h2>
                    <p class="dashboard-section-text">Procesamiento inicial de la solicitud.</p>
                </div>

                <div class="form-actions">
                    <form method="POST" action="{{ route('admin.signup-requests.approve', $signupRequest) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            Aprobar
                        </button>
                    </form>
                </div>

                <form method="POST" action="{{ route('admin.signup-requests.reject', $signupRequest) }}" class="form">
                    @csrf

                    <div class="form-group">
                        <label for="review_notes" class="form-label">Motivo / nota interna</label>
                        <textarea id="review_notes" name="review_notes" class="form-control"
                            rows="4">{{ old('review_notes') }}</textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-secondary">
                            Rechazar
                        </button>
                    </div>
                </form>
            @endif
        </x-card>
    </x-page>
@endsection