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

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Secciones de la solicitud">
                <button type="button"
                    class="tabs-link is-active"
                    data-tab-link="request"
                    role="tab"
                    aria-selected="true">
                    Solicitud
                </button>

                @if ($ownerInvitation)
                    <button type="button"
                        class="tabs-link"
                        data-tab-link="invitation"
                        role="tab"
                        aria-selected="false">
                        Invitación
                    </button>
                @endif

                @if ($signupRequest->status === 'pending')
                    <button type="button"
                        class="tabs-link"
                        data-tab-link="actions"
                        role="tab"
                        aria-selected="false">
                        Acciones
                    </button>
                @endif
            </div>

            <section class="tab-panel is-active" data-tab-panel="request">
                <div class="tab-panel-stack">

                    <x-card>
                        <div class="summary-inline-grid">
                            <div class="summary-inline-card">
                                <div class="summary-inline-label">Estado</div>
                                <div class="summary-inline-value">
                                    <span class="{{ $statusClass }}">
                                        {{ $signupRequest->status }}
                                    </span>
                                </div>
                            </div>

                            <div class="summary-inline-card">
                                <div class="summary-inline-label">Empresa</div>
                                <div class="summary-inline-value">{{ $signupRequest->company_name }}</div>
                            </div>

                            <div class="summary-inline-card">
                                <div class="summary-inline-label">Email</div>
                                <div class="summary-inline-value">{{ $signupRequest->requested_email }}</div>
                            </div>

                            <div class="summary-inline-card">
                                <div class="summary-inline-label">Fecha de creación</div>
                                <div class="summary-inline-value">{{ $signupRequest->created_at?->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    </x-card>

                    <x-card>
                        <div class="dashboard-section-header">
                            <h2 class="dashboard-section-title">Datos de la solicitud</h2>
                            <p class="dashboard-section-text">Información recibida desde el formulario público.</p>
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
                    </x-card>

                    <x-card>
                        <div class="dashboard-section-header">
                            <h2 class="dashboard-section-title">Seguimiento</h2>
                            <p class="dashboard-section-text">Estado del procesamiento administrativo.</p>
                        </div>

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
                    </x-card>

                </div>
            </section>

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

                <section class="tab-panel" data-tab-panel="invitation" hidden>
                    <div class="tab-panel-stack">

                        <x-card>
                            <div class="summary-inline-grid">
                                <div class="summary-inline-card">
                                    <div class="summary-inline-label">Tipo</div>
                                    <div class="summary-inline-value">{{ $ownerInvitation->type }}</div>
                                </div>

                                <div class="summary-inline-card">
                                    <div class="summary-inline-label">Estado</div>
                                    <div class="summary-inline-value">{{ $ownerInvitation->status }}</div>
                                </div>

                                <div class="summary-inline-card">
                                    <div class="summary-inline-label">Email</div>
                                    <div class="summary-inline-value">{{ $ownerInvitation->email }}</div>
                                </div>

                                <div class="summary-inline-card">
                                    <div class="summary-inline-label">Vencimiento</div>
                                    <div class="summary-inline-value">
                                        @if ($expiresAt)
                                            <span class="{{ $expirationBadgeClass }}">{{ $expirationLabel }}</span>
                                        @else
                                            Sin vencimiento
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </x-card>

                        <x-card>
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
                                    <div class="detail-block-value">{{ $ownerInvitation->status }}</div>
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
                                            <span class="{{ $expirationBadgeClass }}">{{ $expirationLabel }}</span>
                                            <div class="helper-inline" style="margin-top: 0.35rem;">
                                                {{ $expiresAt->format('d/m/Y H:i') }}
                                                @if ($humanDiff)
                                                    · {{ $humanDiff }}
                                                @endif
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
                            </div>
                        </x-card>

                        <x-card>
                            <div class="dashboard-section-header">
                                <h2 class="dashboard-section-title">Link de acceso</h2>
                                <p class="dashboard-section-text">Enlace que utilizará el owner para completar el alta inicial.</p>
                            </div>

                            <div class="form">
                                <div class="form-group">
                                    <label for="owner-invitation-link" class="form-label">URL de acceso</label>
                                    <input id="owner-invitation-link" type="text" class="form-control" value="{{ $invitationUrl }}"
                                        readonly data-action="app-select-on-click">
                                    <div class="form-help">
                                        Puedes copiar este enlace y compartirlo manualmente con la persona solicitante.
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary"
                                        data-action="app-copy-target"
                                        data-copy-target="#owner-invitation-link"
                                        data-copy-feedback="Link copiado"
                                        data-copy-feedback-reset="Copiar link">
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
                            </div>
                        </x-card>

                    </div>
                </section>
            @endif

            @if ($signupRequest->status === 'pending')
                <section class="tab-panel" data-tab-panel="actions" hidden>
                    <div class="tab-panel-stack">

                        <x-card>
                            <div class="dashboard-section-header">
                                <h2 class="dashboard-section-title">Aprobación</h2>
                                <p class="dashboard-section-text">Aprueba la solicitud para generar la invitación inicial del owner.</p>
                            </div>

                            <div class="form-actions">
                                <form method="POST" action="{{ route('admin.signup-requests.approve', $signupRequest) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        Aprobar
                                    </button>
                                </form>
                            </div>
                        </x-card>

                        <x-card>
                            <div class="dashboard-section-header">
                                <h2 class="dashboard-section-title">Rechazo</h2>
                                <p class="dashboard-section-text">Registra un motivo o nota interna antes de rechazar la solicitud.</p>
                            </div>

                            <form method="POST" action="{{ route('admin.signup-requests.reject', $signupRequest) }}" class="form">
                                @csrf

                                <div class="form-group">
                                    <label for="review_notes" class="form-label">Motivo / nota interna</label>
                                    <textarea id="review_notes" name="review_notes" class="form-control" rows="4">{{ old('review_notes') }}</textarea>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-secondary">
                                        Rechazar
                                    </button>
                                </div>
                            </form>
                        </x-card>

                    </div>
                </section>
            @endif
        </div>
    </x-page>
@endsection