{{-- FILE: resources/views/tenants/partials/profile-pending-invitations.blade.php --}}

<x-card>
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">Invitaciones pendientes</h2>
        <p class="dashboard-section-text">
            Enlaces generados para esta empresa y todavía no aceptados.
        </p>
    </div>

    @if ($pendingInvitations->count())
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Vencimiento</th>
                        <th class="compact-actions-cell"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingInvitations as $invitation)
                        @php
                            $invitationUrl = route('invitation.accept.show', $invitation->token);
                            $expiresAt = $invitation->expires_at;
                            $isExpired = $expiresAt && $expiresAt->isPast();
                            $isExpiringSoon = $expiresAt && !$isExpired && now()->diffInHours($expiresAt, false) <= 48;

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
                            <td>{{ $invitation->created_at?->format('d/m/Y H:i') }}</td>

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

                            <td class="compact-actions-cell">
                                <div class="compact-actions">
                                    <button type="button" class="btn btn-secondary btn-icon" title="Copiar link"
                                        aria-label="Copiar link" data-action="app-copy-value"
                                        data-copy-value="{{ $invitationUrl }}" data-copy-feedback="✓"
                                        data-copy-feedback-reset="">
                                        <x-icons.copy />
                                    </button>

                                    <form method="POST" action="{{ route('tenant.invitations.destroy', $invitation) }}"
                                        data-action="app-confirm-submit" data-confirm-message="¿Eliminar invitación?">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger btn-icon"
                                            title="Eliminar invitación" aria-label="Eliminar invitación">
                                            <x-icons.trash />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="mb-0">No hay invitaciones pendientes para esta empresa.</p>
    @endif
</x-card>
