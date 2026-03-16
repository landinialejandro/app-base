{{-- FILE: resources/views/tenants/partials/profile-invite-card.blade.php --}}

<x-card>
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">Invitar usuario</h2>
        <p class="dashboard-section-text">
            Genera un link de acceso para compartir manualmente por WhatsApp o cualquier otro medio.
        </p>
    </div>

    <form method="POST" action="{{ route('tenant.invitations.store') }}" class="form">
        @csrf

        <div class="form-group">
            <label for="invite_email" class="form-label">Correo electrónico</label>
            <input id="invite_email" name="email" type="email" class="form-control" value="{{ old('email') }}"
                placeholder="correo@empresa.com" required>
            <div class="form-help">
                El sistema generará un enlace individual para esta empresa.
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Generar link
            </button>
        </div>
    </form>

    @if (!empty($generatedInvitation))
        @php
            $generatedInvitationUrl = route('invitation.accept.show', $generatedInvitation->token);
        @endphp

        <hr style="margin: 1rem 0; border: 0; border-top: 1px solid #d9e1ec;">

        <div class="form-group">
            <label for="generated-invitation-link" class="form-label">Link generado</label>
            <input id="generated-invitation-link" type="text" class="form-control"
                value="{{ $generatedInvitationUrl }}" readonly data-action="app-select-on-click">
            <div class="form-help">
                Copia este enlace y compártelo manualmente con la persona invitada.
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" data-action="app-copy-target"
                data-copy-target="#generated-invitation-link" data-copy-feedback="Link copiado"
                data-copy-feedback-reset="Copiar link">
                Copiar link
            </button>
        </div>
    @endif
</x-card>
